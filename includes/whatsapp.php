<?php
/**
 * CRCAP — Classe WhatsApp
 * Suporta: Meta Cloud API (oficial) + Evolution API (open source)
 *
 * Uso:
 *   $wpp = WhatsApp::fromSettings($pdo);
 *   $wpp->sendTemplate('5596999990000', 'crcap', ['João', 'Texto da mensagem']);
 *   $wpp->sendCampaign($campaignId);
 */
class WhatsApp
{
    private string $provider;   // 'meta' | 'evolution'
    private string $apiUrl;
    private string $apiKey;
    private string $instance;   // Evolution: nome da instância
    private string $phoneId;    // Meta: Phone Number ID
    private PDO    $pdo;

    // ── Construtor ────────────────────────────────────────────────────────────
    public function __construct(
        PDO    $pdo,
        string $provider  = 'meta',
        string $apiUrl    = '',
        string $apiKey    = '',
        string $instance  = '',
        string $phoneId   = ''
    ) {
        $this->pdo      = $pdo;
        $this->provider = $provider;
        $this->apiUrl   = rtrim($this->sanitizeApiUrl($apiUrl), '/');
        $this->apiKey   = $apiKey;
        $this->instance = $instance;
        $this->phoneId  = $phoneId;
    }

    // ── Factory: lê do banco ──────────────────────────────────────────────────
    public static function fromSettings(PDO $pdo): self
    {
        $rows = $pdo->query(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return new self(
            $pdo,
            $rows['whatsapp_provider']  ?? 'meta',
            $rows['whatsapp_api_url']   ?? 'https://graph.facebook.com/v22.0',
            $rows['whatsapp_api_key']   ?? '',
            $rows['whatsapp_instance']  ?? '',
            $rows['whatsapp_phone_id']  ?? ''
        );
    }

    // ── Verifica se está minimamente configurado ──────────────────────────────
    public function isConfigured(): bool
    {
        if ($this->provider === 'meta') {
            return !empty($this->apiKey) && !empty($this->phoneId);
        }
        return !empty($this->apiUrl) && !empty($this->apiKey) && !empty($this->instance);
    }

    // ── Status / conexão ──────────────────────────────────────────────────────
    public function getStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['connected' => false, 'message' => 'API não configurada'];
        }

        try {
            if ($this->provider === 'meta') {
                return $this->metaStatus();
            }
            return $this->evolutionStatus();
        } catch (Exception $e) {
            return ['connected' => false, 'message' => $e->getMessage()];
        }
    }

    // ── ENVIO PRINCIPAL: Template (Meta obrigatório) ──────────────────────────
    /**
     * Envia via template aprovado na Meta.
     *
     * @param string $to           Número E.164 sem + (ex: 5596981003862)
     * @param string $templateName Nome do template no Meta Business Manager
     * @param array  $params       Valores para {{1}}, {{2}}, {{3}}...
     * @param string $lang         Código do idioma (padrão pt_BR)
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array  $params = [],
        string $lang   = 'pt_BR'
    ): array {
        $to = $this->normalizePhone($to);

        if ($this->provider === 'meta') {
            return $this->metaSendTemplate($to, $templateName, $params, $lang);
        }

        // Evolution não usa templates — envia o texto montado
        $text = implode("\n\n", $params);
        return $this->evolutionSendText($to, $text);
    }

    // ── Envio de texto simples ────────────────────────────────────────────────
    /**
     * Para Meta: só funciona dentro da janela de 24h (resposta do usuário).
     * Para campanhas frias, use sendTemplate().
     */
    public function sendText(string $to, string $message): array
    {
        $to = $this->normalizePhone($to);

        if ($this->provider === 'meta') {
            return $this->metaSendText($to, $message);
        }
        return $this->evolutionSendText($to, $message);
    }

    // ── Envio de imagem ───────────────────────────────────────────────────────
    public function sendImage(string $to, string $imageUrl, string $caption = ''): array
    {
        $to = $this->normalizePhone($to);

        if ($this->provider === 'meta') {
            return $this->metaSendImage($to, $imageUrl, $caption);
        }
        return $this->evolutionSendImage($to, $imageUrl, $caption);
    }

    // ── Envio de documento PDF ────────────────────────────────────────────────
    public function sendDocument(string $to, string $docUrl, string $filename = 'documento.pdf'): array
    {
        $to = $this->normalizePhone($to);

        if ($this->provider === 'meta') {
            return $this->metaSendDocument($to, $docUrl, $filename);
        }
        return $this->evolutionSendDocument($to, $docUrl, $filename);
    }

    // ── Disparar campanha completa ────────────────────────────────────────────
    public function sendCampaign(int $campaignId): array
    {
        // Busca campanha
        $camp = $this->db()->fetch(
            "SELECT * FROM whatsapp_campaigns WHERE id = ?", [$campaignId]
        );
        if (!$camp) throw new Exception("Campanha #$campaignId não encontrada");

        // Marca como enviando
        $this->db()->exec(
            "UPDATE whatsapp_campaigns SET status='sending', total_recipients=0,
             sent_count=0, failed_count=0 WHERE id=?",
            [$campaignId]
        );

        // Monta lista de destinatários
        $recipients = $this->buildRecipients($camp);
        $total      = count($recipients);

        // Atualiza total
        $this->db()->exec(
            "UPDATE whatsapp_campaigns SET total_recipients=? WHERE id=?",
            [$total, $campaignId]
        );

        // Monta URL de base para mídia/docs
        $base = $this->appBase();

        // Resolve imagem
        $imageUrl = null;
        if (!empty($camp['media_id'])) {
            $media = $this->db()->fetch(
                "SELECT file_path FROM media WHERE id=?", [(int)$camp['media_id']]
            );
            if ($media) $imageUrl = $base . '/' . ltrim($media['file_path'], '/');
        }

        // Resolve documento PDF
        $docUrl   = null;
        $docName  = 'documento.pdf';
        if (!empty($camp['document_id'])) {
            $doc = $this->db()->fetch(
                "SELECT file_path, file_name FROM documents WHERE id=?", [(int)$camp['document_id']]
            );
            if ($doc) {
                $docUrl  = $base . '/' . ltrim($doc['file_path'], '/');
                $docName = $doc['file_name'] ?? 'documento.pdf';
            }
        }

        // Template a usar (Meta)
        $templateName = trim($camp['template_name'] ?? '') ?: 'crcap';

        $sent = $failed = 0;

        foreach ($recipients as $i => $r) {
            $phone = $r['phone'];
            $name  = $r['name'] ?? '';

            // Substitui variáveis da mensagem
            $msgText = $this->replaceVars($camp['message'], $name);

            try {
                // ── Meta Cloud API ────────────────────────────────────────────
                if ($this->provider === 'meta') {
                    // body params: {{1}} = nome, {{2}} = mensagem
                    // link_url vai como componente button (não como body param)
                    $params    = [$name ?: 'Prezado(a)', $msgText];
                    $buttonUrl = !empty($camp['link_url']) ? $camp['link_url'] : null;
                    $this->metaSendTemplate($phone, $templateName, $params, 'pt_BR', $buttonUrl);

                    // 2. Se tiver imagem, envia depois (mensagem separada)
                    if ($imageUrl) {
                        usleep(500000); // 0.5s
                        $this->metaSendImage($phone, $imageUrl);
                    }

                    // 3. Se tiver PDF, envia depois
                    if ($docUrl) {
                        usleep(500000);
                        $this->metaSendDocument($phone, $docUrl, $docName);
                    }

                // ── Evolution API ─────────────────────────────────────────────
                } else {
                    $this->evolutionAntiSpamDelay($i);

                    if ($imageUrl) {
                        $this->evolutionSendImage($phone, $imageUrl, $msgText);
                    } elseif ($docUrl) {
                        $this->evolutionSendText($phone, $msgText);
                        usleep(800000);
                        $this->evolutionSendDocument($phone, $docUrl, $docName);
                    } else {
                        $this->evolutionTyping($phone);
                        $this->evolutionSendText($phone, $msgText);
                    }

                    if (!empty($camp['link_url'])) {
                        usleep(400000);
                        $this->evolutionSendText($phone, $camp['link_url']);
                    }
                }

                // Log sucesso
                $this->logSend($campaignId, $phone, 'sent');
                $sent++;

            } catch (Exception $e) {
                $this->logSend($campaignId, $phone, 'failed', $e->getMessage());
                $failed++;
            }

            // Atualiza contadores no banco a cada 5 mensagens
            if (($i + 1) % 5 === 0) {
                $this->db()->exec(
                    "UPDATE whatsapp_campaigns SET sent_count=?, failed_count=? WHERE id=?",
                    [$sent, $failed, $campaignId]
                );
            }
        }

        // Finaliza
        $this->db()->exec(
            "UPDATE whatsapp_campaigns
             SET status='sent', sent_count=?, failed_count=?, sent_at=NOW()
             WHERE id=?",
            [$sent, $failed, $campaignId]
        );

        return ['sent' => $sent, 'failed' => $failed, 'total' => $total];
    }

    // ═════════════════════════════════════════════════════════════════════════
    // META CLOUD API — Implementações
    // ═════════════════════════════════════════════════════════════════════════

    private function metaStatus(): array
    {
        $url  = "https://graph.facebook.com/v22.0/{$this->phoneId}?access_token={$this->apiKey}";
        $resp = $this->curl('GET', $url);

        if (!empty($resp['id'])) {
            $num = $resp['display_phone_number'] ?? $this->phoneId;
            return ['connected' => true, 'message' => "Conectado · +$num"];
        }

        $err = $resp['error']['message'] ?? 'Erro desconhecido';
        return ['connected' => false, 'message' => $err];
    }

    private function metaSendText(string $to, string $message): array
    {
        return $this->metaPost([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $message, 'preview_url' => false],
        ]);
    }

    private function metaSendTemplate(
        string  $to,
        string  $name,
        array   $params    = [],
        string  $lang      = 'pt_BR',
        ?string $buttonUrl = null   // URL dinâmica do botão "Visit website", se houver
    ): array {
        $components = [];

        // ── Componente BODY ──────────────────────────────────────────────────
        // Só envia se o template realmente tiver variáveis ({{1}}, {{2}}...)
        // Filtra params vazios para evitar erro #132018
        $bodyParams = array_values(array_filter(
            array_map(fn($v) => trim((string)$v), $params),
            fn($v) => $v !== ''
        ));

        if (!empty($bodyParams)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => $v],
                    $bodyParams
                ),
            ];
        }

        // ── Componente BUTTON (URL dinâmica) ─────────────────────────────────
        // O template "crcap" tem botão "Visit website". Se a URL for dinâmica
        // (contém {{1}} no template do botão), precisamos passar o parâmetro.
        if ($buttonUrl) {
            $components[] = [
                'type'    => 'button',
                'sub_type'=> 'url',
                'index'   => '0',
                'parameters' => [
                    ['type' => 'text', 'text' => $buttonUrl],
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $name,
                'language'   => ['code' => $lang],
                'components' => $components,
            ],
        ];

        // ── Log de debug (ajuda diagnosticar erros #132018) ──────────────────
        $this->debugLog('metaSendTemplate', [
            'to'         => $to,
            'template'   => $name,
            'params_cnt' => count($bodyParams),
            'button_url' => $buttonUrl,
            'payload'    => json_encode($payload),
        ]);

        return $this->metaPost($payload);
    }

    private function metaSendImage(string $to, string $imageUrl, string $caption = ''): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'image',
            'image'             => ['link' => $imageUrl],
        ];
        if ($caption) $payload['image']['caption'] = $caption;
        return $this->metaPost($payload);
    }

    private function metaSendDocument(string $to, string $docUrl, string $filename): array
    {
        return $this->metaPost([
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'document',
            'document'          => [
                'link'     => $docUrl,
                'filename' => $filename,
            ],
        ]);
    }

    private function metaPost(array $payload): array
    {
        $url  = "{$this->apiUrl}/{$this->phoneId}/messages";
        $resp = $this->curl('POST', $url, $payload, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        if (!empty($resp['messages'][0]['id'])) {
            return ['success' => true, 'message_id' => $resp['messages'][0]['id']];
        }

        $err = $resp['error']['message'] ?? json_encode($resp);
        throw new Exception("Meta API: $err");
    }

    // ═════════════════════════════════════════════════════════════════════════
    // EVOLUTION API — Implementações
    // ═════════════════════════════════════════════════════════════════════════

    private function evolutionStatus(): array
    {
        $url  = "{$this->apiUrl}/instance/connectionState/{$this->instance}";
        $resp = $this->curl('GET', $url, null, [
            'apikey: ' . $this->apiKey,
        ]);

        $state = $resp['instance']['state'] ?? $resp['state'] ?? '';

        if ($state === 'open') {
            return ['connected' => true, 'message' => "Conectado · {$this->instance}"];
        }

        return ['connected' => false, 'message' => "Evolution: estado = '$state'"];
    }

    private function evolutionSendText(string $to, string $message): array
    {
        return $this->evolutionPost('/message/sendText/' . $this->instance, [
            'number'  => $to,
            'options' => ['delay' => 1200, 'presence' => 'composing'],
            'textMessage' => ['text' => $message],
        ]);
    }

    private function evolutionSendImage(string $to, string $imageUrl, string $caption = ''): array
    {
        return $this->evolutionPost('/message/sendMedia/' . $this->instance, [
            'number'       => $to,
            'options'      => ['delay' => 1200],
            'mediaMessage' => [
                'mediatype' => 'image',
                'media'     => $imageUrl,
                'caption'   => $caption,
            ],
        ]);
    }

    private function evolutionSendDocument(string $to, string $docUrl, string $filename): array
    {
        return $this->evolutionPost('/message/sendMedia/' . $this->instance, [
            'number'       => $to,
            'options'      => ['delay' => 1200],
            'mediaMessage' => [
                'mediatype' => 'document',
                'media'     => $docUrl,
                'fileName'  => $filename,
            ],
        ]);
    }

    private function evolutionTyping(string $to): void
    {
        try {
            $this->evolutionPost('/chat/sendPresence/' . $this->instance, [
                'number'   => $to,
                'presence' => 'composing',
                'delay'    => rand(1500, 3500),
            ]);
        } catch (Exception $e) { /* silencioso */ }
    }

    private function evolutionAntiSpamDelay(int $index): void
    {
        // 4s–12s de delay aleatório entre mensagens
        $delay = rand(4, 12) * 1_000_000;

        // A cada 30 mensagens, pausa de 45s
        if ($index > 0 && $index % 30 === 0) {
            sleep(45);
            return;
        }

        // Horário comercial (08h–20h no fuso de Brasília)
        $hour = (int)date('H', time() - 3 * 3600);
        if ($hour < 8 || $hour >= 20) {
            throw new Exception('Disparo interrompido: fora do horário permitido (08h–20h).');
        }

        usleep($delay);
    }

    private function evolutionPost(string $endpoint, array $payload): array
    {
        $url  = $this->apiUrl . $endpoint;
        $resp = $this->curl('POST', $url, $payload, [
            'apikey: ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        if (!empty($resp['key']) || !empty($resp['status']) || isset($resp['id'])) {
            return ['success' => true, 'data' => $resp];
        }

        $err = $resp['message'] ?? $resp['error'] ?? json_encode($resp);
        throw new Exception("Evolution API: $err");
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /** Monta lista de destinatários conforme grupo selecionado na campanha */
    private function buildRecipients(array $camp): array
    {
        $list = [];

        if ($camp['recipient_group'] === 'newsletter') {
            $rows = $this->db()->fetchAll(
                "SELECT email AS name, '' AS phone FROM newsletters WHERE status='confirmed'"
            );
            // Newsletter não tem phone — filtra os que têm phone no campo extra se houver
            // Por ora, retorna vazio (newsletter é por email, não WhatsApp)
            return [];
        }

        if ($camp['recipient_group'] === 'manual') {
            $lines = preg_split('/\r?\n/', $camp['manual_recipients'] ?? '');
            foreach ($lines as $line) {
                $line = trim($line);
                if (!$line) continue;
                $parts = explode(',', $line, 2);
                $phone = preg_replace('/\D/', '', $parts[0]);
                if (strlen($phone) >= 8) {
                    $list[] = ['phone' => $phone, 'name' => trim($parts[1] ?? '')];
                }
            }
            return $list;
        }

        // whatsapp_list (padrão)
        $rows = $this->db()->fetchAll(
            "SELECT phone, name FROM whatsapp_contacts WHERE status='active' ORDER BY id"
        );
        foreach ($rows as $r) {
            $phone = preg_replace('/\D/', '', $r['phone']);
            if (strlen($phone) >= 8) {
                $list[] = ['phone' => $phone, 'name' => $r['name'] ?? ''];
            }
        }
        return $list;
    }

    /** Substitui variáveis {{nome}}, {{data}}, {{ano}} */
    private function replaceVars(string $text, string $name = ''): string
    {
        return str_replace(
            ['{{nome}}', '{{data}}', '{{ano}}'],
            [$name ?: 'Prezado(a)', date('d/m/Y'), date('Y')],
            $text
        );
    }

    /** Normaliza número para formato E.164 sem + (ex: 5596981003862) */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        // Adiciona 55 se não tiver DDI
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }
        return $phone;
    }

    /** Remove Phone ID e /messages da URL caso o usuário cole errado */
    private function sanitizeApiUrl(string $url): string
    {
        $url = rtrim($url, '/');
        $url = preg_replace('#/\d{10,}/messages$#', '', $url);
        $url = preg_replace('#/\d{10,}$#', '', $url);
        return rtrim($url, '/');
    }

    /** URL base do site para montar URLs de mídia */
    private function appBase(): string
    {
        if (function_exists('appBase')) return appBase();
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($proto . '://' . $host, '/');
    }

    /** Loga envio individual na tabela whatsapp_logs */
    private function logSend(int $campaignId, string $phone, string $status, string $error = ''): void
    {
        try {
            $this->db()->exec(
                "INSERT INTO whatsapp_logs (campaign_id, phone, status, error_message) VALUES (?,?,?,?)",
                [$campaignId, $phone, $status, $error ?: null]
            );
        } catch (Exception $e) { /* silencioso */ }
    }

    /** Grava debug em arquivo de log para diagnosticar erros de template */
    private function debugLog(string $context, array $data): void
    {
        try {
            $logDir  = __DIR__ . '/../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/whatsapp-debug.log';
            $line    = '[' . date('Y-m-d H:i:s') . '] ' . $context . ' ' . json_encode($data) . PHP_EOL;
            file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {}
    }

    /** cURL genérico */
    private function curl(string $method, string $url, ?array $body = null, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new Exception("cURL: $err");

        $decoded = json_decode($raw, true);
        if ($decoded === null) throw new Exception("Resposta inválida (HTTP $code): " . substr($raw, 0, 200));

        return $decoded;
    }

    /** Proxy para os helpers do db.php (dbFetch / dbFetchAll / dbExec) */
    private function db(): object
    {
        $pdo = $this->pdo;
        return new class($pdo) {
            public function __construct(private PDO $pdo) {}

            public function fetch(string $sql, array $p = []): ?array
            {
                $st = $this->pdo->prepare($sql);
                $st->execute($p);
                $r = $st->fetch(PDO::FETCH_ASSOC);
                return $r ?: null;
            }

            public function fetchAll(string $sql, array $p = []): array
            {
                $st = $this->pdo->prepare($sql);
                $st->execute($p);
                return $st->fetchAll(PDO::FETCH_ASSOC);
            }

            public function exec(string $sql, array $p = []): void
            {
                $st = $this->pdo->prepare($sql);
                $st->execute($p);
            }
        };
    }
}