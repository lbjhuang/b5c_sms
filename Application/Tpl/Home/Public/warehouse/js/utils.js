function getUrlParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function downloadFile(data, filename) {
    let downloadUrl = URL.createObjectURL(data);
    let a = document.createElement('a')
    a.download = filename
    a.href = downloadUrl;
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
}