let inputTypes = {
    'options_radio': 'radio',
    'options_checkbox': 'checkbox',
};

// When select/option change
const register_input_type_select = el => {
    el.addEventListener('change', () => {
        let optionsDom = el.parentNode.parentNode.querySelector('[data-js-selector=input_options]');
        if (Object.keys(inputTypes).includes(el.value)) {
            optionsDom.classList.remove('d-none');

            optionsDom.querySelectorAll('input[type=radio], input[type=checkbox]').forEach(input => {
                input.setAttribute('type', inputTypes[el.value])
            })
        } else {
            optionsDom.classList.add('d-none');
        }
    });
};
document.querySelectorAll('[data-js-selector=input_type_select]').forEach(register_input_type_select);

// Add/delete input option
const register_action_btn_delete_option = btn => {
    btn.addEventListener('click', evt => {
        evt.preventDefault();
        btn.parentElement.remove();
    })
};
document.querySelectorAll('[data-js-selector=delete_input_option]').forEach(register_action_btn_delete_option);
const register_add_input_option = btn => {
    btn.addEventListener('click', evt => {
        evt.preventDefault();
        let clone = document.querySelector('[data-js-selector=input_option_template]').firstChild.cloneNode(true);
        clone.querySelector("[name^='inputs[]']").setAttribute('name', 'inputs[' + btn.parentNode.parentNode.getAttribute('data-input-id') + "][input_choices][]");
        clone.querySelector('input').setAttribute('type', inputTypes[btn.parentElement.parentElement.querySelector('[data-js-selector=input_type_select]').value]);

        btn.parentNode.insertBefore(clone, btn);
        register_action_btn_delete_option(clone.querySelector('[data-js-selector=delete_input_option]'));
    });
};
document.querySelectorAll('[data-js-selector=add_input_option]').forEach(register_add_input_option);

// Add/delete input
const register_action_btn_delete_input = btn => {
    btn.addEventListener('click', evt => {
        evt.preventDefault();
        btn.parentNode.parentNode.remove();
    });
};
document.querySelectorAll('[data-js-selector=delete_input]').forEach(register_action_btn_delete_input);
document.querySelectorAll('[data-js-selector=add_input]').forEach(btn => {
    btn.addEventListener('click', evt => {
        evt.preventDefault();
        let clone = document.querySelector('[data-js-selector=input_template]').firstChild.cloneNode(true);
        let newId = "new][" + document.querySelectorAll('[data-input-id*=new]').length;
        clone.firstChild.setAttribute('data-input-id', newId);
        clone.querySelectorAll("[name^='inputs[]']").forEach(el => {
            el.setAttribute('name', el.getAttribute('name').replace('inputs[', 'inputs[' + newId));
        });

        btn.parentNode.parentNode.insertBefore(clone, btn.parentNode);
        register_action_btn_delete_input(clone.querySelector('[data-js-selector=delete_input]'));
        register_input_type_select(clone.querySelector('[data-js-selector=input_type_select]'));
        register_add_input_option(clone.querySelector('[data-js-selector=add_input_option]'));
    });
});