document.querySelectorAll('.etatOrderBtn').forEach(btn => {
    btn.addEventListener('click', evt => {
        evt.preventDefault();
        let [action, idEtat] = [btn.getAttribute('data-action'), btn.getAttribute('data-etat-id')];
        let originInput = document.querySelector('#order_etat_' + idEtat);
        // si (up + pas le 1e) OU (down + pas le dernier)
        if ((action == 'up' && originInput.value > 0)
            || (action == "down" && originInput.value < (document.getElementById('etats_produit').querySelectorAll('[data-order]').length - 1))) {
            // on va switcher les valeurs entre origin et target
            let [originValue, targetValue] = [originInput.value, parseInt(originInput.value) + (action == 'up' ? -1 : 1)];
            let [originDiv, targetDiv] = [originValue, targetValue].map(v => document.getElementById('etats_produit').querySelector('[data-order="' + v + '"]'));
            let targetInput = targetDiv.querySelector('input[name="order_etat[]"]');
            // js : div [data-order=x], css : div [style="order x"], html : input [value=x]
            // origin
            originDiv.setAttribute('data-order', targetValue);
            originDiv.style = 'order:' + targetValue;
            originInput.value = targetValue;
            // target
            targetDiv.setAttribute('data-order', originValue);
            targetDiv.style = 'order:' + originValue;
            targetInput.value = originValue;
        }
    })
});