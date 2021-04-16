var v =  Number(new Date().getMonth()+''+new Date().getDate());
var t = ''
if(location.host.indexOf('erp.gshopper.com') !== -1){
  t = '<script src="//static-web.gshopper.com/tongji/stat4.min.js?v='+v+'"></script>'
}else{
  t = '<script src="//stage-static-web.gshopper.com/tongji/stat4.js?v='+v+'"></script>'
}
document.write(t)