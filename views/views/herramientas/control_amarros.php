<?php 
ob_start(); 
$pageTitle = 'Herramientas - Control de Amarros';
?>

<div class="card">
    <div class="card-header">
        <h2>Control y Revisión de Amarros</h2>
        <div class="header-actions">
            <a href="https://app-dddd8fce-b79e-4d27-a1c4-0e2ef9614b81.cleverapps.io/" target="_blank" class="btn btn-primary" style="background-color: #6f42c1; border-color: #6f42c1;">
                🚀 Abrir Herramienta Externa
            </a>
        </div>
    </div>
    
    <div class="tool-content">
        <p class="description">
            Esta herramienta permite gestionar la revisión física de los amarros, marcar documentos como verificados y realizar el seguimiento de faltantes.
            Haga clic en el botón superior para acceder al sistema externo de control.
        </p>

        <div class="carousel-container">
            <div class="carousel-slide" id="carouselSlide">
                <div class="carousel-item">
                    <img src="/assets/img/screenshots/control-amarros-1.png" alt="Pantalla de Inicio" loading="lazy">
                    <p class="caption">Pantalla de Inicio</p>
                </div>
                <div class="carousel-item">
                    <img src="/assets/img/screenshots/control-amarros-2.png" alt="Lista de verificación" loading="lazy">
                    <p class="caption">Lista de verificación</p>
                </div>
                <div class="carousel-item">
                    <img src="/assets/img/screenshots/control-amarros-3.png" alt="Gestión de Amarros" loading="lazy">
                    <p class="caption">Gestión de Amarros</p>
                </div>
            </div>
            
            <button class="carousel-btn prev-btn" id="prevBtn">&#10094;</button>
            <button class="carousel-btn next-btn" id="nextBtn">&#10095;</button>
            
            <div class="carousel-indicators" id="carouselIndicators">
                <span class="indicator active" data-index="0"></span>
                <span class="indicator" data-index="1"></span>
                <span class="indicator" data-index="2"></span>
            </div>
        </div>
    </div>
</div>

<style>
.tool-content {
    padding: 20px;
}

.description {
    font-size: 1.1em;
    color: #555;
    margin-bottom: 30px;
    max-width: 800px;
}

/* Carousel Styles */
.carousel-container {
    position: relative;
    max-width: 900px;
    margin: 20px auto;
    overflow: hidden;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    background: #fff;
}

.carousel-slide {
    display: flex;
    /* width: 300%; Removed fixed width */
    transition: transform 0.5s ease-in-out;
}

.carousel-item {
    min-width: 100%; /* Occupy full container width */
    width: 100%;
    flex-shrink: 0;
    position: relative;
}

.carousel-item img {
    width: 100%;
    height: auto;
    display: block;
    max-height: 500px;
    object-fit: contain;
    background: #f8f9fa;
}

.caption {
    padding: 15px;
    text-align: center;
    color: #333;
    font-weight: 600;
    margin: 0;
    border-top: 1px solid #eee;
    background: white;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    font-size: 24px;
    padding: 15px;
    cursor: pointer;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
    user-select: none;
    z-index: 2;
}

.carousel-btn:hover {
    background: rgba(0, 0, 0, 0.8);
}

.prev-btn { left: 20px; }
.next-btn { right: 20px; }

.carousel-indicators {
    position: absolute;
    bottom: 60px; /* Above caption */
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 2;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.indicator.active {
    background: #fff;
    transform: scale(1.2);
    border-color: #333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slide = document.getElementById('carouselSlide');
    const items = document.querySelectorAll('.carousel-item');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const indicators = document.querySelectorAll('.indicator');
    
    let counter = 0;
    const size = 100 / items.length; // Percentage width of each item slide logic based on 100% per item container width

    // Initial State
    updateCarousel();

    nextBtn.addEventListener('click', () => {
        if (counter >= items.length - 1) {
            counter = 0;
        } else {
            counter++;
        }
        updateCarousel();
    });

    prevBtn.addEventListener('click', () => {
        if (counter <= 0) {
            counter = items.length - 1;
        } else {
            counter--;
        }
        updateCarousel();
    });

    indicators.forEach(ind => {
        ind.addEventListener('click', (e) => {
            counter = parseInt(e.target.getAttribute('data-index'));
            updateCarousel();
        });
    });

    function updateCarousel() {
        slide.style.transform = `translateX(${-counter * 100}%)`;
        
        indicators.forEach((ind, index) => {
            if (index === counter) {
                ind.classList.add('active');
            } else {
                ind.classList.remove('active');
            }
        });
    }
    
    // Auto slide optional
    // setInterval(() => {
    //     nextBtn.click();
    // }, 5000);
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
