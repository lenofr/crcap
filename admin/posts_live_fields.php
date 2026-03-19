<?php
$post = $post ?? [];
?>

<!-- ════════ TRANSMISSÃO AO VIVO ════════ -->
<div class="bg-white rounded-2xl border border-[#001644]/6 shadow-sm p-6 mt-6">
  <div class="flex items-center gap-3 mb-5">
    <div class="relative w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
      <i class="fas fa-circle text-red-500 text-sm" id="liveIconPreview"
         style="<?= !empty($post['is_live']) && $post['is_live'] ? '' : 'opacity:.3' ?>"></i>
    </div>
    <div>
      <h3 class="text-sm font-bold text-[#001644]">Transmissão ao Vivo</h3>
      <p class="text-[10px] text-[#022E6B]">Ativa o player de live na página inicial substituindo o slider</p>
    </div>
    <!-- Toggle ao vivo -->
    <div class="ml-auto flex items-center gap-2">
      <span class="text-xs text-[#001644]/50" id="liveLabelOff">Inativo</span>
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" name="is_live" id="isLive" value="1"
               class="sr-only peer"
               <?= !empty($post['is_live']) && $post['is_live'] ? 'checked' : '' ?>
               onchange="toggleLiveFields(this.checked)">
        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer
                    peer-checked:bg-red-500 transition after:content-[''] after:absolute after:top-0.5
                    after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                    peer-checked:after:translate-x-5"></div>
      </label>
      <span class="text-xs font-bold text-red-500 hidden" id="liveLabelOn">🔴 AO VIVO</span>
    </div>
  </div>

  <div id="liveFields" class="<?= !empty($post['is_live']) && $post['is_live'] ? '' : 'hidden' ?> space-y-4">

    <!-- URL da transmissão -->
    <div>
      <label class="block text-xs font-semibold text-[#001644] mb-1.5">
        URL / Link da Transmissão <span class="text-red-500">*</span>
      </label>
      <div class="flex gap-2">
        <input type="url" name="live_url" id="liveUrl"
               value="<?= htmlspecialchars($post['live_url'] ?? '') ?>"
               placeholder="https://youtube.com/watch?v=... ou https://fb.me/live/..."
               class="flex-1 px-3 py-2.5 rounded-xl border border-[#001644]/15 text-xs focus:outline-none focus:border-[#BF8D1A] focus:ring-1 focus:ring-[#BF8D1A]/20"
               oninput="detectPlatform(this.value)">
        <button type="button" onclick="testLiveUrl()"
                class="px-4 py-2.5 bg-[#001644] text-white text-xs font-bold rounded-xl hover:bg-[#022E6B] transition flex items-center gap-1.5 flex-shrink-0">
          <i class="fas fa-play text-[10px]"></i> Testar
        </button>
      </div>
      <p class="text-[10px] text-[#022E6B]/60 mt-1">
        Suporta: YouTube, Facebook, Instagram, Twitch, Zoom e links diretos de embed
      </p>
    </div>

    <!-- Plataforma detectada / manual -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div>
        <label class="block text-xs font-semibold text-[#001644] mb-1.5">Plataforma</label>
        <select name="live_platform" id="livePlatform"
                class="w-full px-3 py-2.5 rounded-xl border border-[#001644]/15 text-xs focus:outline-none focus:border-[#BF8D1A]">
          <option value="">Detectar automaticamente</option>
          <option value="youtube"   <?= ($post['live_platform'] ?? '') === 'youtube'   ? 'selected' : '' ?>>📺 YouTube</option>
          <option value="facebook"  <?= ($post['live_platform'] ?? '') === 'facebook'  ? 'selected' : '' ?>>📘 Facebook</option>
          <option value="instagram" <?= ($post['live_platform'] ?? '') === 'instagram' ? 'selected' : '' ?>>📷 Instagram</option>
          <option value="twitch"    <?= ($post['live_platform'] ?? '') === 'twitch'    ? 'selected' : '' ?>>🟣 Twitch</option>
          <option value="zoom"      <?= ($post['live_platform'] ?? '') === 'zoom'      ? 'selected' : '' ?>>🎥 Zoom</option>
          <option value="custom"    <?= ($post['live_platform'] ?? '') === 'custom'    ? 'selected' : '' ?>>🔗 Outro / Embed</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#001644] mb-1.5">Início da Live</label>
        <input type="datetime-local" name="live_started_at" id="liveStartedAt"
               value="<?= htmlspecialchars($post['live_started_at'] ?? date('Y-m-d\TH:i')) ?>"
               class="w-full px-3 py-2.5 rounded-xl border border-[#001644]/15 text-xs focus:outline-none focus:border-[#BF8D1A]">
      </div>
      <div>
        <label class="block text-xs font-semibold text-[#001644] mb-1.5">Término previsto</label>
        <input type="datetime-local" name="live_ended_at" id="liveEndedAt"
               value="<?= htmlspecialchars($post['live_ended_at'] ?? '') ?>"
               class="w-full px-3 py-2.5 rounded-xl border border-[#001644]/15 text-xs focus:outline-none focus:border-[#BF8D1A]">
      </div>
    </div>

    <!-- Preview do embed -->
    <div id="livePreviewWrap" class="hidden">
      <label class="block text-xs font-semibold text-[#001644] mb-1.5">Preview</label>
      <div class="rounded-xl overflow-hidden bg-black aspect-video max-h-48">
        <iframe id="livePreviewFrame" src="" class="w-full h-full border-0"
                allow="autoplay; fullscreen" allowfullscreen></iframe>
      </div>
    </div>

    <!-- Botão enviar push notification -->
    <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl border border-red-100">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
          <i class="fas fa-bell text-red-500 text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-[#001644]">Notificação Push</p>
          <p class="text-[10px] text-[#022E6B]/60">Avisar todos os inscritos que a live começou</p>
        </div>
      </div>
      <button type="button" onclick="sendLivePush()"
              class="px-4 py-2 bg-red-500 text-white text-xs font-bold rounded-xl hover:bg-red-600 transition flex items-center gap-1.5">
        <i class="fas fa-paper-plane text-[10px]"></i> Enviar Push
      </button>
    </div>

    <!-- Encerrar live -->
    <?php if (!empty($post['is_live']) && $post['is_live']): ?>
    <div class="flex items-center justify-between p-4 bg-[#001644]/3 rounded-xl border border-[#001644]/8">
      <div class="flex items-center gap-3">
        <i class="fas fa-stop-circle text-[#001644]/40 text-xl"></i>
        <div>
          <p class="text-xs font-bold text-[#001644]">Encerrar transmissão</p>
          <p class="text-[10px] text-[#022E6B]/60">Remove o player da home e restaura o slider</p>
        </div>
      </div>
      <button type="button" onclick="endLive(<?= $post['id'] ?? 0 ?>)"
              class="px-4 py-2 bg-[#001644] text-white text-xs font-bold rounded-xl hover:bg-[#022E6B] transition">
        Encerrar Live
      </button>
    </div>
    <?php endif; ?>

  </div><!-- /liveFields -->
</div>

<script>
// ── Live fields toggle ─────────────────────────────
function toggleLiveFields(on) {
  document.getElementById('liveFields').classList.toggle('hidden', !on);
  document.getElementById('liveLabelOn').classList.toggle('hidden', !on);
  document.getElementById('liveLabelOff').classList.toggle('hidden', on);
  document.getElementById('liveIconPreview').style.opacity = on ? '1' : '.3';
  if (on && !document.getElementById('liveStartedAt').value) {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('liveStartedAt').value = now.toISOString().slice(0,16);
  }
}

// ── Auto-detect platform from URL ─────────────────
function detectPlatform(url) {
  const sel = document.getElementById('livePlatform');
  if (!url) { sel.value = ''; return; }
  if (url.includes('youtube.com') || url.includes('youtu.be')) sel.value = 'youtube';
  else if (url.includes('facebook.com') || url.includes('fb.me')) sel.value = 'facebook';
  else if (url.includes('instagram.com')) sel.value = 'instagram';
  else if (url.includes('twitch.tv')) sel.value = 'twitch';
  else if (url.includes('zoom.us')) sel.value = 'zoom';
  else sel.value = 'custom';
}

// ── Build embed URL ────────────────────────────────
function buildEmbedUrl(url, platform) {
  if (!url) return '';
  try {
    if (platform === 'youtube' || url.includes('youtube') || url.includes('youtu.be')) {
      let vid = '';
      const patterns = [
        /[?&]v=([^&#]+)/,          // watch?v=ID
        /youtu\.be\/([^?#]+)/,   // youtu.be/ID
        /\/live\/([^?#]+)/,      // /live/ID
        /\/embed\/([^?#]+)/,     // already embed
        /\/shorts\/([^?#]+)/,    // shorts
      ];
      for (const p of patterns) {
        const m = url.match(p);
        if (m) { vid = m[1]; break; }
      }
      if (!vid) return '';
      return `https://www.youtube.com/embed/${vid}?autoplay=1&mute=1&rel=0&modestbranding=1`;
    }
    if (platform === 'twitch' || url.includes('twitch.tv')) {
      const ch = url.split('/').filter(Boolean).pop();
      return `https://player.twitch.tv/?channel=${ch}&parent=${location.hostname}&autoplay=true`;
    }
    if (platform === 'facebook' || url.includes('facebook')) {
      return `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(url)}&autoplay=true`;
    }
    // Custom / direct embed
    return url;
  } catch(e) { return ''; }
}

// ── Test live URL preview ──────────────────────────
function testLiveUrl() {
  const url      = document.getElementById('liveUrl').value.trim();
  const platform = document.getElementById('livePlatform').value;
  if (!url) { alert('Cole a URL da transmissão primeiro.'); return; }
  const embed = buildEmbedUrl(url, platform);
  if (!embed) {
    if (confirm('Não foi possível gerar embed automático.\nAbrir o link diretamente em nova aba?')) {
      window.open(url, '_blank');
    }
    return;
  }
  const wrap  = document.getElementById('livePreviewWrap');
  const frame = document.getElementById('livePreviewFrame');
  // Force reload by clearing src first
  frame.src = 'about:blank';
  setTimeout(() => { frame.src = embed; }, 50);
  wrap.classList.remove('hidden');
  wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Send push notification ─────────────────────────
async function sendLivePush() {
  const title = document.querySelector('[name="title"]')?.value || 'Transmissão ao Vivo';
  if (!confirm(`Enviar push para todos os inscritos?

"🔴 ${title}"`)) return;
  try {
    const r = await fetch('/crcap/api/send-live-push.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: '🔴 AO VIVO — CRCAP', body: title, url: '/crcap/' })
    });
    const text = await r.text();
    let d;
    try { d = JSON.parse(text); } catch(e) {
      alert('❌ Resposta inválida do servidor:\n' + text.substring(0,300));
      console.error('Raw response:', text); return;
    }
    if (r.status === 403) { alert('❌ Sessão expirada. Recarregue a página e tente novamente.'); return; }
    alert(d.ok
      ? (d.count > 0 ? `✅ Push enviado para ${d.count} inscritos!` : `ℹ️ ${d.note || 'Nenhum inscrito ainda.'}`)
      : '❌ Erro: ' + d.msg);
  } catch(e) {
    alert('Erro de rede ao enviar push.');
    console.error(e);
  }
}

// ── End live ──────────────────────────────────────
async function endLive(postId) {
  if (!confirm('Encerrar a transmissão ao vivo? O slider voltará à home.')) return;
  try {
    const r = await fetch('/crcap/api/end-live.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ post_id: postId })
    });
    const text = await r.text();
    let d;
    try { d = JSON.parse(text); } catch(e) {
      alert('❌ Resposta inválida do servidor:\n' + text.substring(0,300));
      console.error('Raw response:', text); return;
    }
    if (r.status === 403) { alert('❌ Sessão expirada. Recarregue a página e tente novamente.'); return; }
    if (d.ok) { alert('✅ Live encerrada! O slider voltará à home.'); location.reload(); }
    else alert('❌ Erro: ' + d.msg);
  } catch(e) {
    alert('Erro de rede ao encerrar live.');
    console.error(e);
  }
}

// Init state on load
document.addEventListener('DOMContentLoaded', () => {
  const cb = document.getElementById('isLive');
  if (cb) toggleLiveFields(cb.checked);
});
</script>