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

// https://www.w3schools.com/howto/howto_js_sort_table.asp
const sortTable = (n, tableDOM) => {
    let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    switching = true;
    // Set the sorting direction to ascending:
    dir = "asc";
    /* Make a loop that will continue until
    no switching has been done: */
    while (switching) {
        // Start by saying: no switching is done:
        switching = false;
        rows = tableDOM.querySelectorAll('tbody tr');
        /* Loop through all table rows from tbody */
        for (i = 0; i < (rows.length - 1); i++) {
            // Start by saying there should be no switching:
            shouldSwitch = false;
            /* Get the two elements you want to compare,
            one from current row and one from the next: */
            x = rows[i].childNodes[n];
            y = rows[i + 1].childNodes[n];
            /* Check if the two rows should switch place,
            based on the direction, asc or desc: */
            if (dir == "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            /* If a switch has been marked, make the switch
            and mark that a switch has been done: */
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            // Each time a switch is done, increase this count by 1:
            switchcount++;
        } else {
            /* If no switching has been done AND the direction is "asc",
            set the direction to "desc" and run the while loop again. */
            if (switchcount == 0 && dir == "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}

document.querySelectorAll('table.sortable-table').forEach(tDOM => {
    tDOM.querySelectorAll('thead th').forEach((header, i) => {
        header.innerHTML = `<div>` + header.innerHTML + `</div><span class="cursor-pointer btn btn-sm btn-outline-secondary"><i class="material-icons">sort</i> Trier</span>`;
    });
    const sortBtns = tDOM.querySelectorAll('thead th .btn');
    tDOM.querySelectorAll('thead th .btn').forEach((header, i) => {
        header.addEventListener('click', evt => {
            sortBtns.forEach(b => b.classList.remove('active'));
            evt.target.classList.toggle('active');
            sortTable(i, tDOM);
        });
    });
});