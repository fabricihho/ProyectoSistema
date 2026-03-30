<?php
$pageTitle = 'Varita Mágica ✨';
ob_start();
?>

<div class="card" style="text-align: center; padding: 40px; min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <h1 style="color: #1B3C84; margin-bottom: 20px;">Varita Mágica ✨</h1>
    <p style="color: #666; font-size: 1.2em;">¡Haz clic en el objeto para transformarlo!</p>
    
    <div id="magic-container" onclick="transformar()" style="cursor: pointer; font-size: 120px; margin: 40px; transition: transform 0.3s ease, opacity 0.3s ease; user-select: none;">
        🦆
    </div>

    <!-- Botón Remitente (Oculto por defecto) -->
    <button id="btn-remitente" class="btn btn-primary" style="display: none; margin-top: 10px;" onclick="transformarEnPato()">Remitente</button>
    
    <div style="margin-top: 20px;">
        <p>¡Descubre todos los objetos sorpresa! 🎲</p>
    </div>
</div>

<script>
    const objetos = [
        '🦆', '🐸', '🎈', '🐶', '🐱', '🐷', '🍦', '🍕', '🚀', '⭐', 
        '👻', '👽', '🦄', '🤖', '🔥', '☀️', '🌙', '🌸', '⚽', '🧽',
        '🐮', '🐑', '🐴', '🐔', '🐵', '🦁', '🐯', '🐻', '🐼', '🐨',
        '🐘', '🦒', '🦓', '🦘', '🐇', '🐹', '🦜', '🐊', '🐋', '🐙'
    ];
    let indice = 0;
    const container = document.getElementById('magic-container');
    const btnRemitente = document.getElementById('btn-remitente');

    function transformar() {
        // Animación de desaparición
        container.style.transform = 'scale(0.1) rotate(180deg)';
        container.style.opacity = '0';
        
        setTimeout(() => {
            // Cambiar objeto aleatoriamente (asegurar que no se repita el mismo)
            let nuevoIndice;
            do {
                nuevoIndice = Math.floor(Math.random() * objetos.length);
            } while (nuevoIndice === indice);
            
            indice = nuevoIndice;
            actualizarEmoji();
        }, 300);
    }

    function transformarEnPato() {
        // Animación de desaparición
        container.style.transform = 'scale(0.1) rotate(180deg)';
        container.style.opacity = '0';
        
        setTimeout(() => {
            // Buscar índice del pato
            const patoIndex = objetos.indexOf('🦆');
            if (patoIndex !== -1) {
                indice = patoIndex;
            }
            actualizarEmoji();
        }, 300);
    }

    function actualizarEmoji() {
        const emoji = objetos[indice];
        container.innerHTML = emoji;
        
        // Mostrar botón si es un gallo
        if (emoji === '🐔') {
            btnRemitente.style.display = 'block';
        } else {
            btnRemitente.style.display = 'none';
        }

        // Animación de aparición
        container.style.transform = 'scale(1.2) rotate(0deg)';
        container.style.opacity = '1';
        
        // Efecto de rebote
        setTimeout(() => {
            container.style.transform = 'scale(1)';
        }, 200);
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
