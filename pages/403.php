<?php
http_response_code(403);
$pageTitle = 'Acesso negado · CRCAP';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $pageTitle ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-[#F8FAFC] min-h-screen flex flex-col">
<header class="bg-[#001644] py-4 px-6">
    <a href="/crcap/" class="flex items-center gap-3 w-fit">
        <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center text-white font-bold text-lg">C</div>
        <span class="text-white font-bold">CRCAP</span>
    </a>
</header>
<main class="flex-1 flex items-center justify-center p-8">
    <div class="text-center max-w-lg">
        <div class="text-[8rem] font-black text-[#001644]/5 leading-none mb-0">403</div>
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center mx-auto -mt-8 mb-6 shadow-xl">
            <i class="fas fa-lock text-3xl text-[#BF8D1A]"></i>
        </div>
        <h1 class="text-2xl font-bold text-[#001644] mb-2" style="font-family:'DM Serif Display',serif">Acesso negado</h1>
        <p class="text-[#022E6B] text-sm mb-8 leading-relaxed">
            Você não tem permissão para acessar esta página.<br>
            Faça login com as credenciais corretas ou entre em contato se acredita que há um erro.
        </p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="/crcap/pages/login.php" class="px-6 py-2.5 bg-[#BF8D1A] text-white rounded-xl text-sm font-semibold hover:bg-[#001644] transition">
                <i class="fas fa-sign-in-alt mr-2"></i>Fazer Login
            </a>
            <a href="/crcap/" class="px-6 py-2.5 bg-white border border-[#001644]/10 text-[#001644] rounded-xl text-sm font-semibold hover:border-[#BF8D1A] transition">
                <i class="fas fa-home mr-2"></i>Início
            </a>
        </div>
    </div>
</main>
</body>
</html>
