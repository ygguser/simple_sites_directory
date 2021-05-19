function deletesiteClick(elem) {
    let dsbl = elem.checked;
    document.getElementById("input_description").disabled = dsbl;
    document.getElementById("input_domain").disabled = dsbl;
    document.getElementById("select_categories").disabled = dsbl;
    document.getElementById("descr_necess_mark").style.visibility = dsbl ? 'hidden' : 'visible';
    return 0;
}
