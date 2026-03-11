@yield('header')

<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Charging...</span>
    </div>
    <span class="spinner-text">Charging...</span>
</div>
<style>
    /* Estilo del contenedor del spinner */
    .spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    /* Estilo del spinner */
    .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 0.3em;
    }

    .spinner-text {
        color: white;
        margin-left: 10px;
        font-size: 1.2rem;
    }
</style>
