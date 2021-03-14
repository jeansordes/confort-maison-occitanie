let commentForm = document.getElementById('commentForm');

if (commentForm) {
    commentForm.querySelector('textarea').addEventListener('keypress', event => {
        if ((event.keyCode == 10 || event.keyCode == 13 || event.key == 'Enter') && event.ctrlKey) {
            event.preventDefault();
            commentForm.submit();
            return true;
        }
    });
}

[...document.getElementsByClassName('backlink-js')].forEach(b => { b.addEventListener('click', () => window.history.back()) });