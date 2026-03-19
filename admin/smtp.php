<?php
$pageTitle = 'SMTP / E-mail · Admin CRCAP';
$activeAdm = 'smtp';
require_once __DIR__ . '/admin_header.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['form_smtp'])){
    $d=['name'=>trim($_POST['name']??''),'host'=>trim($_POST['host']??''),'port'=>(int)($_POST['port']??587),'username'=>trim($_POST['username']??''),'encryption'=>$_POST['encryption']??'tls','from_email'=>trim($_POST['from_email']??''),'from_name'=>trim($_POST['from_name']??''),'is_active'=>isset($_POST['is_active'])?1:0,'is_default'=>isset($_POST['is_default'])?1:0,'daily_limit'=>(int)($_POST['daily_limit']??0)];
    if(!empty($_POST['password']))$d['password']=base64_encode($_POST['password']);
    $id=(int)($_POST['id']??0);
    try{
        if($id){$sets=implode(',',array_map(fn($k)=>"`$k`=?",array_keys($d)));dbExec($pdo,"UPDATE smtp_settings SET $sets WHERE id=?",[...array_values($d),$id]);}
        else{$keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($d)));$phs=implode(',',array_fill(0,count($d),'?'));dbExec($pdo,"INSERT INTO smtp_settings ($keys) VALUES ($phs)",array_values($d));}
        if($d['is_default'])dbExec($pdo,"UPDATE smtp_settings SET is_default=0 WHERE id!=?",[$id?:(int)$pdo->lastInsertId()]);
        $msg='saved';
    }catch(Exception $e){$msg='error';}
}

if(isset($_GET['delete']))dbExec($pdo,"DELETE FROM smtp_settings WHERE id=?",[(int)$_GET['delete']]);
$configs=dbFetchAll($pdo,"SELECT * FROM smtp_settings ORDER BY is_default DESC,created_at DESC");
$editConfig=isset($_GET['edit'])?dbFetch($pdo,"SELECT * FROM smtp_settings WHERE id=?",[(int)$_GET['edit']]):null;
?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2"><i class="fas fa-server text-[#BF8D1A]"></i>Configurações SMTP</h2>
</div>
<?php if($msg==='saved'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">Configuração salva!</div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Form -->
    <div class="card p-6">
        <h3 class="font-bold text-[#001644] text-sm mb-5"><?= $editConfig?'Editar':'Nova' ?> Configuração SMTP</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="form_smtp" value="1">
            <?php if($editConfig): ?><input type="hidden" name="id" value="<?= $editConfig['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2"><label class="form-label">Nome/Identificação</label><input type="text" name="name" value="<?= htmlspecialchars($editConfig['name']??'') ?>" required class="form-input" placeholder="Ex: Gmail Corporativo"></div>
                <div class="col-span-2"><label class="form-label">Host SMTP</label><input type="text" name="host" value="<?= htmlspecialchars($editConfig['host']??'') ?>" required class="form-input" placeholder="smtp.gmail.com"></div>
                <div><label class="form-label">Porta</label><input type="number" name="port" value="<?= $editConfig['port']??587 ?>" class="form-input"></div>
                <div><label class="form-label">Criptografia</label><select name="encryption" class="form-input"><?php foreach(['tls'=>'TLS','ssl'=>'SSL','none'=>'Nenhuma'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($editConfig['encryption']??'tls')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">Usuário</label><input type="text" name="username" value="<?= htmlspecialchars($editConfig['username']??'') ?>" class="form-input" placeholder="email@dominio.com"></div>
                <div><label class="form-label">Senha <?= $editConfig?'(em branco = manter)':'' ?></label><input type="password" name="password" class="form-input" placeholder="••••••••"></div>
                <div><label class="form-label">E-mail Remetente</label><input type="email" name="from_email" value="<?= htmlspecialchars($editConfig['from_email']??'') ?>" class="form-input" placeholder="noreply@crcap.org.br"></div>
                <div><label class="form-label">Nome Remetente</label><input type="text" name="from_name" value="<?= htmlspecialchars($editConfig['from_name']??'') ?>" class="form-input" placeholder="CRCAP"></div>
                <div><label class="form-label">Limite Diário</label><input type="number" name="daily_limit" value="<?= $editConfig['daily_limit']??0 ?>" class="form-input" placeholder="0 = ilimitado"></div>
            </div>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="is_active" <?= ($editConfig['is_active']??1)?'checked':'' ?>><span class="text-xs font-semibold text-[#001644]">Ativo</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="is_default" <?= ($editConfig['is_default']??0)?'checked':'' ?>><span class="text-xs font-semibold text-[#001644]">Padrão</span></label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
                <?php if($editConfig): ?><a href="/crcap/admin/smtp.php" class="flex-1 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition text-center">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Lista -->
    <div class="space-y-4">
        <h3 class="font-bold text-[#001644] text-sm">Configurações Salvas</h3>
        <?php if(empty($configs)): ?>
        <div class="card p-8 text-center text-[#001644]/30 text-xs">Nenhuma configuração salva</div>
        <?php else: foreach($configs as $c): ?>
        <div class="card p-4 border <?= $c['is_default']?'border-[#BF8D1A]/40':'border-[#001644]/5' ?>">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold text-[#001644] text-sm"><?= htmlspecialchars($c['name']) ?></p>
                <div class="flex gap-1.5">
                    <?php if($c['is_default']): ?><span class="badge badge-gold">Padrão</span><?php endif; ?>
                    <span class="badge <?= $c['is_active']?'badge-green':'badge-gray' ?>"><?= $c['is_active']?'Ativo':'Inativo' ?></span>
                </div>
            </div>
            <p class="text-xs text-[#022E6B]"><?= htmlspecialchars($c['host']) ?>:<?= $c['port'] ?> (<?= $c['encryption'] ?>)</p>
            <p class="text-xs text-[#022E6B]"><?= htmlspecialchars($c['from_name']) ?> &lt;<?= htmlspecialchars($c['from_email']) ?>&gt;</p>
            <div class="flex gap-2 mt-3">
                <a href="?edit=<?= $c['id'] ?>" class="btn-primary py-1.5 text-[10px]"><i class="fas fa-edit"></i>Editar</a>
                <button type="button" onclick="testSmtp(<?= $c['id'] ?>, this)"
                        class="px-3 py-1.5 text-[10px] font-semibold rounded-xl border border-[#001644]/20 text-[#001644] hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition flex items-center gap-1">
                    <i class="fas fa-plug"></i>Testar
                </button>
                <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Excluir?')" class="btn-danger py-1.5 text-[10px]"><i class="fas fa-trash"></i>Excluir</a>
            </div>
            <div id="smtpResult-<?= $c['id'] ?>" class="hidden mt-2 text-xs rounded-lg px-3 py-2"></div>
        </div>
        <?php endforeach;endif;?>
    </div>
</div>


<div class="card p-5 mt-6">
    <h3 class="font-bold text-[#001644] text-sm mb-4 flex items-center gap-2">
        <i class="fas fa-paper-plane text-[#BF8D1A]"></i>Enviar E-mail de Teste
    </h3>
    <div class="flex gap-3">
        <input type="email" id="testEmailAddr" class="form-input flex-1" placeholder="destinatario@email.com">
        <button onclick="sendTestEmail()" class="btn-primary">
            <i class="fas fa-paper-plane"></i>Enviar Teste
        </button>
    </div>
    <div id="sendTestResult" class="hidden mt-3 text-xs rounded-lg px-3 py-2"></div>
</div>

<script>
async function testSmtp(id, btn) {
    const res    = document.getElementById('smtpResult-' + id);
    const orig   = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando…';
    btn.disabled  = true;
    res.className = 'mt-2 text-xs rounded-lg px-3 py-2 bg-blue-50 text-blue-700';
    res.classList.remove('hidden');
    res.textContent = 'Verificando conexão…';

    try {
        const r    = await fetch('/crcap/api/test-smtp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ smtp_id: id })
        });
        const data = await r.json();
        res.className = 'mt-2 text-xs rounded-lg px-3 py-2 ' +
            (data.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200');
        res.innerHTML = (data.success ? '✅ ' : '❌ ') + (data.message || 'Erro desconhecido');
    } catch(e) {
        res.className = 'mt-2 text-xs rounded-lg px-3 py-2 bg-red-50 text-red-700 border border-red-200';
        res.textContent = '❌ Erro: ' + e.message;
    }

    btn.innerHTML = orig;
    btn.disabled  = false;
}

async function sendTestEmail() {
    const email = document.getElementById('testEmailAddr').value;
    if (!email) { alert('Informe um e-mail de destino.'); return; }
    const res   = document.getElementById('sendTestResult');
    res.className = 'mt-3 text-xs rounded-lg px-3 py-2 bg-blue-50 text-blue-700';
    res.classList.remove('hidden');
    res.textContent = 'Enviando e-mail de teste…';

    try {
        const r    = await fetch('/crcap/api/test-smtp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ send_test: true, test_email: email })
        });
        const data = await r.json();
        res.className = 'mt-3 text-xs rounded-lg px-3 py-2 ' +
            (data.test_sent ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200');
        res.innerHTML = data.test_sent
            ? '✅ E-mail enviado para <strong>' + email + '</strong>! Verifique sua caixa de entrada.'
            : '❌ Falha no envio: ' + (data.test_errors?.[0] || data.message || 'Erro desconhecido');
    } catch(e) {
        res.className = 'mt-3 text-xs rounded-lg px-3 py-2 bg-red-50 text-red-700 border border-red-200';
        res.textContent = '❌ ' + e.message;
    }
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>