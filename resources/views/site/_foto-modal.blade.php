<dialog class="modal modal--foto" id="modal-foto" aria-labelledby="modal-foto-titulo">
    <div class="modal__caixa modal__caixa--foto">
        <button class="modal__x" type="button" data-fecha-modal aria-label="Fechar">&times;</button>

        <div class="modal__ficha">

            <h3 id="modal-foto-titulo"></h3>

            <p class="modal__onde">
                <span id="modal-foto-onde"></span>
                <span class="modal__contador" id="modal-foto-contador" aria-hidden="true"></span>
            </p>

            <p class="modal__descricao" id="modal-foto-descricao" hidden></p>

            <p class="modal__rodape-ficha">

                <span id="modal-foto-credito" class="modal__credito" hidden></span>
                <a class="btn" id="modal-foto-link" href="#" hidden>Ver a noite inteira</a>
            </p>
        </div>

        <figure class="modal__foto">
            <button class="modal__seta modal__seta--antes" type="button" data-foto-anterior aria-label="Foto anterior" hidden>&#8249;</button>
            <img id="modal-foto-imagem" src="" alt="">
            <button class="modal__seta modal__seta--depois" type="button" data-foto-proxima aria-label="Próxima foto" hidden>&#8250;</button>
        </figure>
    </div>
</dialog>
