/*  对象baseNormal  */
var baseNormal=window.baseNormal||{};
/*  get class  */
baseNormal.getElementsByClassName = function(oElm, strTagName, strClassName){
    var arrElements = (strTagName == "*" && oElm.all)? oElm.all :
    oElm.getElementsByTagName(strTagName);
    var arrReturnElements = new Array();
    strClassName = strClassName.replace(/-/g, "\-");
    var oRegExp = new RegExp("(^|\\s)" + strClassName + "(\\s|$)");
    var oElement;
    for(var i=0; i < arrElements.length; i++){
    oElement = arrElements[i];
    if(oRegExp.test(oElement.className)){
    arrReturnElements.push(oElement);
    }
    }
    return (arrReturnElements)
}

baseNormal.hasClass = function(obj, cls) {  
    return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));  
}  
  
baseNormal.addClass = function(obj, cls) {  
    if (!this.hasClass(obj, cls)) obj.className += " " + cls;  
}  
  
baseNormal.removeClass = function(obj, cls) {  
    if (this.hasClass(obj, cls)) {  
        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');  
        obj.className = obj.className.replace(reg, ' ');  
    }  
}  
  
baseNormal.toggleClass = function(obj,cls){  
    if(this.hasClass(obj,cls)){  
        this.removeClass(obj, cls);  
    }else{  
        this.addClass(obj, cls);  
    }  
}  
  
baseNormal.toggleClassTest = function(){  
    var obj = document. getElementById('test');  
    this.toggleClass(obj,"testClass");  
}  
