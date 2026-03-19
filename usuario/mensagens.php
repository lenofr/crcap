<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = 'Minhas Mensagens · CRCAP';

$pg  = max(1, (int)($_GET['p'] ?? 1));
$pp  = 10;
$off = ($pg-1)*$pp;

$messages = dbFetchAll($pdo,
    "SELECT * FROM contacts WHERE email=? ORDER BY created_at DESC LIMIT $pp OFFSET $off",
    [$user['email']]);
$total = dbFetch($pdo, "SELECT COUNT(*) AS n FROM contacts WHERE email=?", [$user['email']])['n'] ?? 0;
$pages = ceil($total / $pp);

$statusLabels = ['new'=>'Recebida','read'=>'Lida','replied'=>'Respondida','archived'=>'Arquivada'];
$statusColors = ['new'=>'badge-blue','read'=>'badge-gray','replied'=>'badge-green','archived'=>'badge-gray'];

include __DIR__ . '/../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] to-[#022E6B] text-white py-10">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-4">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/usuario/perfil.php" class="hover:text-white transition">Meu Perfil</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Minhas Mensagens</span>
        </nav>
        <h1 class="font-serif text-2xl font-bold">Minhas Mensagens</h1>
        <p class="text-white/70 text-sm mt-1">Histórico de mensagens enviadas ao CRCAP</p>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-4 gap-8">
        <aside class="space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/5 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xl font-bold">
                        <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-[#001644] text-sm"><?= h($user['full_name'] ?? $user['username']) ?></p>
                        <p class="text-[10px] text-[#022E6B]"><?= h($user['email']) ?></p>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="/crcap/usuario/perfil.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-user w-4 text-center"></i>Meu Perfil</a>
                    <a href="/crcap/usuario/inscricoes.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-calendar-check w-4 text-center"></i>Minhas Inscrições</a>
                    <a href="/crcap/usuario/downloads.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-download w-4 text-center"></i>Downloads</a>
                    <a href="/crcap/usuario/mensagens.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white"><i class="fas fa-envelope w-4 text-center"></i>Minhas Mensagens</a>
                </nav>
            </div>

            <a href="/crcap/pages/contato.php" class="flex items-center gap-3 p-4 bg-[#001644] rounded-2xl text-white hover:bg-[#022E6B] transition">
                <i class="fas fa-paper-plane text-xl text-[#BF8D1A]"></i>
                <div>
                    <p class="font-bold text-sm">Nova Mensagem</p>
                    <p class="text-white/60 text-xs">Falar com o CRCAP</p>
                </div>
            </a>

            <a href="/crcap/pages/ouvidoria.php" class="flex items-center gap-3 p-4 bg-white rounded-2xl border border-[#001644]/5 shadow-sm hover:border-[#BF8D1A]/30 transition">
                <i class="fas fa-bullhorn text-xl text-[#BF8D1A]"></i>
                <div>
                    <p class="font-bold text-[#001644] text-sm">Ouvidoria</p>
                    <p class="text-[#022E6B] text-xs">Registrar solicitação</p>
                </div>
            </a>
        </aside>

        <div class="lg:col-span-3">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-[#022E6B]"><strong class="text-[#001644]"><?= number_format($total) ?></strong> mensagen<?= $total!==1?'s':'' ?> enviada<?= $total!==1?'s':'' ?></p>
                <a href="/crcap/pages/contato.php" class="btn-primary text-xs py-2"><i class="fas fa-plus"></i>Nova mensagem</a>
            </div>

            <?php if (empty($messages)): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-[#001644]/5 shadow-sm">
                <i class="fas fa-inbox text-4xl text-[#001644]/15 mb-4 block"></i>
                <p class="font-semibold text-[#001644] mb-2">Nenhuma mensagem enviada</p>
                <p class="text-xs text-[#022E6B] mb-5">Você ainda não enviou nenhuma mensagem ao CRCAP.</p>
                <a href="/crcap/pages/contato.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#BF8D1A] transition">
                    <i class="fas fa-paper-plane"></i>Enviar mensagem
                </a>
            </div>
            <?php else: ?>

            <div id="sse-lista-msgs" class="space-y-4">
                <?php foreach ($messages as $msg): ?>
                <div class="bg-white rounded-2xl border border-[#001644]/3 shadow-sm p-5 hover:shadow-md hover:border-[#BF8D1A]/20 transition"
                     data-msg-id="<?= (int)$msg['id'] ?>"
                     data-msg-status="<?= h($msg['status']) ?>">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <h3 class="font-bold text-[#001644] text-sm"><?= h($msg['subject'] ?: 'Sem assunto') ?></h3>
                            <p class="text-[10px] text-[#022E6B] mt-0.5">
                                <i class="fas fa-clock text-[#BF8D1A] mr-1"></i>
                                <?= date('d/m/Y \à\s H:i', strtotime($msg['created_at'])) ?>
                                <?php if ($msg['department']): ?>
                                · <span class="font-semibold"><?= h($msg['department']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="msg-badge flex-shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-bold
                            <?= $msg['status']==='replied' ? 'bg-[#006633]/10 text-[#006633]' : ($msg['status']==='read' ? 'bg-[#022E6B]/10 text-[#022E6B]' : 'bg-[#BF8D1A]/10 text-[#BF8D1A]') ?>">
                            <?= $statusLabels[$msg['status']] ?? $msg['status'] ?>
                        </span>
                    </div>
                    <p class="text-xs text-[#022E6B] leading-relaxed bg-[#F8FAFC] rounded-xl p-3 line-clamp-3">
                        <?= h($msg['message']) ?>
                    </p>
                    <?php if ($msg['status'] === 'replied' && $msg['replied_at']): ?>
                    <div class="reply-block mt-3 px-3 py-2.5 bg-[#006633]/5 rounded-xl border border-[#006633]/10">
                        <p class="text-[10px] font-bold text-[#006633] mb-1 flex items-center gap-1">
                            <i class="fas fa-reply"></i> Resposta do CRCAP
                            <span class="font-normal text-[#022E6B]/50 ml-1"><?= date('d/m/Y', strtotime($msg['replied_at'])) ?></span>
                        </p>
                        <?php if (!empty($msg['reply_message'])): ?>
                        <p class="text-xs text-[#022E6B] leading-relaxed"><?= nl2br(h($msg['reply_message'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 mt-6">
                <?php for ($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++): ?>
                <a href="?p=<?= $i ?>" class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-semibold transition <?= $i===$pg?'bg-[#001644] text-white':'bg-white border border-[#001644]/10 hover:border-[#BF8D1A]' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</main>

<!-- ── Notificação SSE ─────────────────────────────────── -->
<style>
@keyframes sse-fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
#sse-notif {
  position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;
  width:320px;background:#fff;border-radius:1rem;
  box-shadow:0 20px 60px rgba(0,22,68,.18),0 4px 16px rgba(0,22,68,.10);
  border:1px solid rgba(0,22,68,.08);overflow:hidden;
  transform:translateY(120%);opacity:0;
  transition:transform .4s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;
  pointer-events:none;
}
#sse-notif.show{transform:translateY(0);opacity:1;pointer-events:auto;}
#sse-notif-bar{height:4px;background:linear-gradient(90deg,#006633,#004d26);}
#sse-notif-body{padding:1rem 1rem .85rem;display:flex;gap:.75rem;align-items:flex-start;}
#sse-notif-icon{width:2.4rem;height:2.4rem;border-radius:.65rem;background:#006633;
  display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;flex-shrink:0;}
#sse-notif-title{font-size:.72rem;font-weight:800;color:#001644;margin-bottom:.2rem;}
#sse-notif-msg{font-size:.68rem;color:#022E6B;line-height:1.5;
  overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
#sse-notif-actions{display:flex;gap:.5rem;padding:0 1rem .85rem;}
#sse-notif-btn{flex:1;padding:.45rem;background:#001644;color:#fff;border:none;
  border-radius:.5rem;font-size:.68rem;font-weight:700;cursor:pointer;
  text-align:center;text-decoration:none;display:block;transition:background .2s;}
#sse-notif-btn:hover{background:#BF8D1A;color:#fff;}
#sse-notif-close{padding:.45rem .75rem;background:#F8FAFC;
  border:1px solid rgba(0,22,68,.08);border-radius:.5rem;
  font-size:.68rem;font-weight:600;color:#022E6B;cursor:pointer;transition:background .2s;}
#sse-notif-close:hover{background:#e8edf4;}
@keyframes sse-pulse{0%,100%{box-shadow:0 0 0 0 rgba(0,102,51,.4)}50%{box-shadow:0 0 0 6px rgba(0,102,51,0)}}
#sse-notif-icon.pulse{animation:sse-pulse 1.5s ease infinite;}
</style>

<div id="sse-notif">
  <div id="sse-notif-bar"></div>
  <div id="sse-notif-body">
    <div id="sse-notif-icon"><i class="fas fa-reply"></i></div>
    <div>
      <div id="sse-notif-title">Nova resposta do CRCAP</div>
      <div id="sse-notif-msg">Sua mensagem foi respondida.</div>
    </div>
  </div>
  <div id="sse-notif-actions">
    <a id="sse-notif-btn" href="#">Ver resposta</a>
    <button id="sse-notif-close" onclick="document.getElementById('sse-notif').classList.remove('show')">Fechar</button>
  </div>
</div>

<script>
(function(){
  var estadoMsgs = {};
  document.querySelectorAll('[data-msg-id]').forEach(function(el){
    estadoMsgs[el.dataset.msgId] = el.dataset.msgStatus;
  });

  var notif      = document.getElementById('sse-notif');
  var notifBar   = document.getElementById('sse-notif-bar');
  var notifIcon  = document.getElementById('sse-notif-icon');
  var notifTitle = document.getElementById('sse-notif-title');
  var notifMsg   = document.getElementById('sse-notif-msg');
  var notifBtn   = document.getElementById('sse-notif-btn');
  var timer      = null;

  var statusLabel = {replied:'Respondida',read:'Lida',new:'Recebida',archived:'Arquivada'};
  var statusCores = {
    replied:'bg-[#006633]/10 text-[#006633]',
    read:   'bg-[#022E6B]/10 text-[#022E6B]',
    new:    'bg-[#BF8D1A]/10 text-[#BF8D1A]',
  };

  function mostrar(titulo, msg, href, cor) {
    notifBar.style.background  = cor || 'linear-gradient(90deg,#006633,#004d26)';
    notifIcon.style.background = cor ? cor.replace('linear-gradient(90deg,','').split(',')[0] : '#006633';
    notifTitle.textContent = titulo;
    notifMsg.textContent   = msg;
    notifBtn.href          = href || '#';
    notifIcon.classList.add('pulse');
    notif.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(function(){ notif.classList.remove('show'); notifIcon.classList.remove('pulse'); }, 12000);
    // Som suave
    try {
      var ctx = new (window.AudioContext||window.webkitAudioContext)();
      var o=ctx.createOscillator(), g=ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.frequency.value=880;
      g.gain.setValueAtTime(0,ctx.currentTime);
      g.gain.linearRampToValueAtTime(.07,ctx.currentTime+.05);
      g.gain.linearRampToValueAtTime(0,ctx.currentTime+.4);
      o.start(ctx.currentTime); o.stop(ctx.currentTime+.4);
    } catch(e){}
  }

  function atualizarCard(el, status, replyMsg, repliedAt) {
    el.dataset.msgStatus = status;
    // Badge
    var badge = el.querySelector('.msg-badge');
    if (badge) {
      badge.className = 'msg-badge flex-shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-bold ' + (statusCores[status]||statusCores['new']);
      badge.textContent = statusLabel[status] || status;
    }
    // Injeta bloco de resposta se vier texto
    if (status === 'replied' && replyMsg && !el.querySelector('.reply-block')) {
      var data = '';
      if (repliedAt) {
        try { data = new Date(repliedAt).toLocaleDateString('pt-BR'); } catch(e){}
      }
      el.insertAdjacentHTML('beforeend',
        '<div class="reply-block mt-3 px-3 py-2.5 bg-[#006633]/5 rounded-xl border border-[#006633]/10" style="animation:sse-fadeIn .5s ease">'
        +'<p class="text-[10px] font-bold text-[#006633] mb-1 flex items-center gap-1"><i class="fas fa-reply"></i> Resposta do CRCAP'
        +(data ? '<span class="font-normal text-[#022E6B]/50 ml-1">'+data+'</span>' : '')+'</p>'
        +'<p class="text-xs text-[#022E6B] leading-relaxed">'+replyMsg.replace(/\n/g,'<br>')+'</p></div>'
      );
    }
  }

  function conectar(){
    var es = new EventSource('/crcap/usuario/sse-usuario.php');

    es.addEventListener('sync_msgs', function(e){
      try {
        var d = JSON.parse(e.data);
        (d.mensagens||[]).forEach(function(m){
          var id   = String(m.id);
          var prev = estadoMsgs[id];
          if (prev === undefined) { estadoMsgs[id] = m.status; return; }
          if (prev !== m.status) {
            estadoMsgs[id] = m.status;
            var el = document.querySelector('[data-msg-id="'+id+'"]');
            if (el) atualizarCard(el, m.status, m.reply_message, m.replied_at);
            if (m.status === 'replied') {
              var assunto = el ? (el.querySelector('h3')?.textContent?.trim()||'') : '';
              var trecho  = m.reply_message
                ? m.reply_message.substring(0,80)+(m.reply_message.length>80?'…':'')
                : 'Sua mensagem foi respondida.';
              mostrar(
                '📬 Nova resposta do CRCAP!',
                assunto ? '"'+assunto+'": '+trecho : trecho,
                '#',
                'linear-gradient(90deg,#006633,#004d26)'
              );
            }
          }
        });
      } catch(err){ console.warn('[SSE msgs]',err); }
    });

    es.addEventListener('reconectar', function(){ es.close(); conectar(); });
    es.onerror = function(){ es.close(); setTimeout(conectar,30000); };
  }

  conectar();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>