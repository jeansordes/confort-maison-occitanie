let commentForm = document.getElementById('commentForm');

commentForm.querySelector('textarea').addEventListener('keypress', event => {
    if ((event.keyCode == 10 || event.keyCode == 13 || event.key == 'Enter') && event.ctrlKey) {
        event.preventDefault();
        commentForm.submit();
        return true;
    }
});