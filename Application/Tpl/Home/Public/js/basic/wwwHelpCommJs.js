var wwwHelpCommJs = window.wwwHelpCommJs||{};
wwwHelpCommJs.url_get_data = function () {
    var t = window.document.location.href.toString();
    t = t.replace(/(\#[\w\.]*$)/g, "");
    var e = t.split("?");
    if ("string" == typeof e[1]) {
        e = e[1].split("&");
        var i = {};
        for (var a in e) {
            var n = e[a].split("=");
            i[n[0]] = n[1]
        }
        return i
    }
    return {}
}

wwwHelpCommJs.getUrlSearchObj = function(url){
  url = url?url:window.document.location.href.toString();
  var result = {};
  var searchIndex = url.indexOf('?');
  var hashIndex = url.indexOf('#')===-1?url.length:url.indexOf('#');
  var searchStr = url.slice(searchIndex+1, hashIndex);
  var searchArr = searchStr.split('&');
  for(var i=0;i<searchArr.length;i++){
    var eleArr = searchArr[i].split('=');
    result[eleArr[0]] = eleArr[1];
  }
  return result;
}
