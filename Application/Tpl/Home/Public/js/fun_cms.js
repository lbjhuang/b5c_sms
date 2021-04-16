var Browser = new Object();

Browser.isMozilla = (typeof document.implementation != 'undefined') && (typeof document.implementation.createDocument != 'undefined') && (typeof HTMLDocument != 'undefined');
Browser.isIE = window.ActiveXObject ? true : false;
Browser.isFirefox = (navigator.userAgent.toLowerCase().indexOf("firefox") != - 1);
Browser.isSafari = (navigator.userAgent.toLowerCase().indexOf("safari") != - 1);
Browser.isOpera = (navigator.userAgent.toLowerCase().indexOf("opera") != - 1);

function rowindex(tr)
{
  if (Browser.isIE)
  {
    return tr.rowIndex;
  }
  else
  {
    table = tr.parentNode.parentNode;
    for (i = 0; i < table.rows.length; i ++ )
    {
      if (table.rows[i] == tr)
      {
        return i;
      }
    }
  }
}


function initLoadDiv(divId){
    if(!document.getElementById(divId)){
        var input = document.createElement("div");
        input.setAttribute("style", "display:block;position:absolute;top:50%;left:50%;");
        input.setAttribute("id", divId);//设置Id属性
        document.body.appendChild(input);
    }
    document.getElementById(divId).innerHTML = '<img src="/Application/Tpl/Home/Public/images/ajax-loader.gif?v=1">';
}


function showAndHide(id,type){
    if( document.getElementById(id) ){
        if( type=='show' ){
            document.getElementById(id).style.display = '';
            setTimeout(function () {
                document.getElementById(id).style.display = 'none';
            },5000);
        }else{
            document.getElementById(id).style.display = 'none';
        }
    }
}


function setJsImport(url){
	thisObj=document.getElementsByTagName("body")[0];
	if( thisObj ){
			var oScript= document.createElement("script"); 
			oScript.type = "text/javascript"; 
			oScript.src=url; 
			thisObj.appendChild( oScript); 
	}
}


function cancel_box() {
	self.parent.tb_remove();return false;
}


function goToModDeals(){
	//document.getElementsByName('ids')[0].value='';
	var dealIds='';
	var arr=document.getElementsByName('batch');
	var count=arr.length;
	var chk=0;
	for(var i=0;i<count;i++){
		if(arr[i].checked==true){
			chk++;
			dealIds+=arr[i].value+'|';
		}
	}
	if(chk==0 || dealIds==''){
		alert('Please choose');
		return false;
	}
	tb_show('','box_dealedit.php?ids='+dealIds+'&TB_iframe=true&height=550&width=880',"thickbox");
	return false;
}


function checkboxAll(obj,name){
	if(obj.checked==true){
		var arr=document.getElementsByName(name);
		var count=arr.length;
		for(var i=0;i<count;i++){
			arr[i].checked=true;
		}

		var objarr=document.getElementsByName(obj.name);
		var count=objarr.length;
		for(var i=0;i<count;i++){
			objarr[i].checked=true;
		}
	}else{
		var arr=document.getElementsByName(name);
		var count=arr.length;
		for(var i=0;i<count;i++){
			arr[i].checked=false;
		}

		var objarr=document.getElementsByName(obj.name);
		var count=objarr.length;
		for(var i=0;i<count;i++){
			objarr[i].checked=false;
		}
	}
	return false;
}

function checkboxIsAll(name){
	var arr=document.getElementsByName(name);
	var count=arr.length;
	var chkcount=0;
	for(var i=0;i<count;i++){
		if(arr[i].checked==true){
			chkcount++;
		}
	}
	if(count==chkcount){
		var objarr=document.getElementsByName(name+'All');
		var count=objarr.length;
		for(var i=0;i<count;i++){
			objarr[i].checked=true;
		}
	}else{
		var objarr=document.getElementsByName(name+'All');
		var count=objarr.length;
		for(var i=0;i<count;i++){
			objarr[i].checked=false;
		}
	}
	return false;
}

function checkboxIsAllName(name,nameAll){
	var arr=document.getElementsByName(name);
	var count=arr.length;
	var chkcount=0;
	for(var i=0;i<count;i++){
		if(arr[i].checked==true){
			chkcount++;
		}
	}
	if(count==chkcount){
		document.getElementsByName(nameAll)[0].checked=true;
	}else{
		document.getElementsByName(nameAll)[0].checked=false;
	}
	return false;
}

function setIdInnerhtml(id,html){
	if( document.getElementById(id) ){
		document.getElementById(id).innerHTML=html;
	}
}

function getCheckboxIds(name){
	var arr=document.getElementsByName(name);
	var count=arr.length;
	if(count==0){	return false;	}
	var chk=0;
	var ids_str='';
	for(var i=0;i<count;i++){
		if(arr[i].checked==true){
			chk++;
			ids_str+=arr[i].value+'|';
		}
	}
	if(chk==0 || ids_str==''){
		return false;
	}
	return ids_str.substr(0,ids_str.length-1);;
}

function getNotCheckboxIds(name){
	var arr=document.getElementsByName(name);
	var count=arr.length;
	if(count==0){	return false;	}
	var chk=0;
	var ids_str='';
	for(var i=0;i<count;i++){
		if(arr[i].checked!=true){
			chk++;
			ids_str+=arr[i].value+'|';
		}
	}
	if(chk==0 || ids_str==''){
		return false;
	}
	return ids_str.substr(0,ids_str.length-1);;
}

function loadImg(){
	return '<img src="images/loading4.gif" >';
}

function ajaxLocality(obj ,selectId ){
	var value=obj.options[obj.selectedIndex].value;
	//if( value=='' ) return false;

	var tmp=arguments[2];
	var	d_id='';
	if(tmp==undefined || tmp==''){
	}else{
		d_id=encodeURI(tmp);
	}

	$.ajax({
		url: 'index.php?ajaxtype=get_district&id='+value+'&d_id='+d_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}


function ajaxSubcategory(obj ,selectId ){
	var value=obj.options[obj.selectedIndex].value;
	if( value=='' ) return false;

	var subcat_id=arguments[2];

	$.ajax({
		url: 'index.php?ajaxtype=get_subcat&cat_id='+value+'&subcat_id='+subcat_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}


//团购编辑页面
function changeBackColor(name ){
	if(document.getElementsByName(name)[0]){
	}else{
		return false;
	}

	var arr=document.getElementsByName(name);
	var count=arr.length;
	var chkcount=0;
	for(var i=0;i<count;i++){
		if(arr[i].checked==true){
			changeClassName(arr[i].parentNode ,'bkcorange' );
		}else{
			changeClassName(arr[i].parentNode ,'' );
		}
	}

	return false;
}

function changeClassName(obj,classname){
	obj.className=classname;
}

function selectedBackColor(obj){
	if(obj.checked==true){
		changeClassName(obj.parentNode ,'bkcorange' );
	}else{
		changeClassName(obj.parentNode ,'' );
	}
}

//显示截断或者全部文字
function open_details_all() {
	$(".details").css("display","");
	$(".nodetails").css("display","none");
}

function hide_details_all() {
	$(".details").css("display","none");
	$(".nodetails").css("display","");
}

function changeShow(type ,id ){
	if(type==1){
		document.getElementById('cshow_'+id).style.display='none';
		document.getElementById('chidden_'+id).style.display='';
	}else{
		document.getElementById('cshow_'+id).style.display='';
		document.getElementById('chidden_'+id).style.display='none';
	}
}

function jsPointShop(addrid,latiid,longid,cityid,distid){
	var address=$("#"+addrid).val();
	$.ajax({
		url: 'tuan_deal.php?',
		type: 'POST',
		data:{
			sub:	'ajaxpoint',
			address:address
		},
		success: function(msg){
			var m=msg.substr(0,2);
			if(m=='ok'){
				var m=msg.substr(2);
				var p_arr=m.split(',');
				$("#"+latiid).val( p_arr[1] );
				$("#"+longid).val( p_arr[0] );
				if( p_arr[2].length>5 ){
					p_city=p_arr[2].substr(5);
					var x=document.getElementById(cityid);
					x.value=p_city;
				}
				if( p_arr[3].length>5 ){
					var p_text=p_arr[3].substr(5);
					ajaxLocality(x ,distid ,p_text );
				}else{
					ajaxLocality(x ,distid );
				}
			}else{
				alert('Failed ');
			}
		}
	});
}

function jsPointShopMapabc(addrid,latiid,longid,cityid,distid){
	var address=$("#"+addrid).val();
	$.ajax({
		url: 'tuan_deal.php?',
		type: 'POST',
		data:{
			sub:	'ajaxpoint',
			maptype: 'mapabc',
			address:address
		},
		success: function(msg){
			var m=msg.substr(0,2);
			if(m=='ok'){
				var m=msg.substr(2);
				var p_arr=m.split(',');
				$("#"+latiid).val( p_arr[1] );
				$("#"+longid).val( p_arr[0] );
				if( p_arr[2].length>5 ){
					p_city=p_arr[2].substr(5);
					var x=document.getElementById(cityid);
					x.value=p_city;
				}
				if( p_arr[3].length>5 ){
					var p_text=p_arr[3].substr(5);
					ajaxLocality(x ,distid ,p_text );
				}else{
					ajaxLocality(x ,distid );
				}
			}else{
				alert('Failed ');
			}
		}
	});
}

function shop_notice(id){
	$("#shop_save_"+id).attr('class','cred');
}


function showorhide(id){
	if( document.getElementById(id) ){
		if( document.getElementById(id).style.display=='none' ){
			$("#"+id).show();
		}else{
			$("#"+id).hide();
		}
	}
}


function ajax_before_timeout(fun){
	$.ajax({
		url: 'index.php?ajaxtype=chk_timeout',
		type: 'GET',
		data:{},
		success: function(msg){
			if(msg=='ok'){
				setTimeout(""+fun+"",0);
			}else{
				alert('Timeout'); return false;
			}
		}
	});
}


function go_href(url){
	location.href=url;
}


function set_multiple_values(name,setname){
	var arr=document.getElementsByTagName('input');
	var count=arr.length;
	document.getElementsByName(setname)[0].value='';
	for(var i=0;i<count;i++){
		if(arr[i].type=='checkbox' ){
			var v = arr[i].id;
			v = v.replace(/\d/g,'');
			if(v==name){
				if(arr[i].checked==true){
					document.getElementsByName(setname)[0].value+=arr[i].value+'|';
				}
			}
		}
	}
	var a=document.getElementsByName(setname)[0].value;
	document.getElementsByName(setname)[0].value=a.substr(0,a.length-1);
}


function num_generate(num,divid){
	var k='';
	var s='abcdefghijklmnopqrstuvwxyz0123456789';
	var n=s.length;
	for(var i=0;i<num;i++){
		var r=Math.random();
		r=Math.round(r*100);
		if(r>n){
			r=Math.round( Math.random()*10 );
		}
		k+=s.substr(r,1);
	}
	$("#"+divid).val(k);
}

//团购国家的省份
function ajax_tuan_provinces(obj ,selectId ){
	var value=obj.options[obj.selectedIndex].value;
	if( value=='' ) return false;

	var now_id=arguments[2];

	$.ajax({
		url: 'index.php?ajaxtype=get_tuan_provinces&country_id='+value+'&now_id='+now_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}
function ajax_tuan_provinces_by_id(obj_value ,selectId ){
	var now_id=arguments[2];
	$.ajax({
		url: 'index.php?ajaxtype=get_tuan_provinces&country_id='+obj_value+'&now_id='+now_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}
//团购省份的城市
function ajax_tuan_cities(obj ,selectId ){
	var value=obj.options[obj.selectedIndex].value;
	//if( value=='' ) return false;

	var now_id=arguments[2];

	$.ajax({
		url: 'index.php?ajaxtype=get_tuan_cities&region_id='+value+'&now_id='+now_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}
function ajax_tuan_cities_by_id(obj_value ,selectId ){
	var now_id=arguments[2];
	$.ajax({
		url: 'index.php?ajaxtype=get_tuan_cities&region_id='+obj_value+'&now_id='+now_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}
//团购城市的地区
function ajax_tuan_dist_by_id(obj_value ,selectId ){
	var now_id=arguments[2];
	$.ajax({
		url: 'index.php?ajaxtype=get_district&id='+obj_value+'&d_id='+now_id,
		type: 'GET',
		data:{},
		success: function(msg){
			$("#"+selectId+"").html( msg );
		}
	});
}

function addressPointMapabc(addrid,latiid,longid,cityid,distid,countryid,provinceid, load_div){
	var address=$("#"+addrid).val();
	$("#show_ajax_wait").show();//加载信息
	if(load_div) $("#"+load_div).show();//加载信息
	$.ajax({
		url: 'index.php?',
		type: 'POST',
		data:{
			ajaxtype:	'address_point',
			maptype: 'mapabc',
			address:address
		},
		success: function(msg){
			var m=msg.substr(0,2);
			if(m=='ok'){
				var str=msg.substr(2);
				var p_arr=str.split(',');
				$("#"+latiid).val( p_arr[1] );
				$("#"+longid).val( p_arr[0] );
				
				var nation_id=region_id=city_id=dist_id='';
				if( p_arr[4].length>7 ){
					nation_id=p_arr[4].substr(7);
					if(countryid!='') $("#"+countryid).val(nation_id);
				}
				if( p_arr[5].length>7 ){
					region_id=p_arr[5].substr(7);
					if(provinceid!='') ajax_tuan_provinces_by_id(nation_id, provinceid, region_id);
				}
				if( p_arr[2].length>=5 ){
					city_id=p_arr[2].substr(5);
					ajax_tuan_cities_by_id(region_id, cityid, city_id);
				}
				if( p_arr[3].length>=5 ){
					dist_id=p_arr[3].substr(5);
					ajax_tuan_dist_by_id(city_id, distid, dist_id);
				}
			}else{
				alert('Failed ');
			}
			$("#show_ajax_wait").hide();//加载信息
			if(load_div) $("#"+load_div).hide();//加载信息
		}
	});
}


function addressPointGoogle(addrid,latiid,longid,cityid,distid,countryid,provinceid, load_div){
	var address=$("#"+addrid).val();
	$("#show_ajax_wait").show();//加载信息
	if(load_div) $("#"+load_div).show();//加载信息
	$.ajax({
		url: 'index.php?',
		type: 'POST',
		data:{
			ajaxtype:	'address_point',
			maptype: 'google',
			address:address
		},
		success: function(msg){
			var m=msg.substr(0,2);
			if(m=='ok'){
				var str=msg.substr(2);
				var p_arr=str.split(',');
				$("#"+latiid).val( p_arr[1] );
				$("#"+longid).val( p_arr[0] );
				
				var nation_id=region_id=city_id=dist_id='';
				if( p_arr[4].length>7 ){
					nation_id=p_arr[4].substr(7);
					if(countryid!='') $("#"+countryid).val(nation_id);
				}
				if( p_arr[5].length>7 ){
					region_id=p_arr[5].substr(7);
					if(provinceid!='') ajax_tuan_provinces_by_id(nation_id, provinceid, region_id);
				}
				if( p_arr[2].length>=5 ){
					city_id=p_arr[2].substr(5);
					ajax_tuan_cities_by_id(region_id, cityid, city_id);
				}
				if( p_arr[3].length>=5 ){
					dist_id=p_arr[3].substr(5);
					ajax_tuan_dist_by_id(city_id, distid, dist_id);
				}
			}else{
				alert('Failed ');
			}
			$("#show_ajax_wait").hide();//加载信息
			if(load_div) $("#"+load_div).hide();//加载信息
		}
	});
}


function left2right(idLeft,idRight){
	var sorttype='';
	if(document.getElementById('toSortType')){
		var sorttype=$("#toSortType").val();
	}
	$("#"+idLeft+" option:selected").each(function(){
		$("#"+idRight).append('<option value="' + $(this).val() + sorttype + '">' + $(this).html() + sorttype + '</option>');
		//$(this).remove();
	});
}
function right2left(idRight,idLeft){
	$("#"+idRight+" option:selected").each(function(){
		//$("#"+idLeft).append("<option value=" + $(this).val() + ">" + $(this).html() + "</option>");//这个方法是默认在后面添加
		$(this).remove();
	});
}

function clone(obj) {  
    var o;  
    if (typeof obj == "object") {  
        if (obj === null) {  
            o = null;  
        } else {  
            if (obj instanceof Array) {  
                o = [];  
                for (var i = 0, len = obj.length; i < len; i++) {  
                    o.push(clone(obj[i]));  
                }  
            } else {  
                o = {};  
                for (var j in obj) {  
                    o[j] = clone(obj[j]);  
                }  
            }  
        }  
    } else {  
        o = obj;  
    }  
    return o;  
}

/**
 *  JavaScript中数组去除重复
 *  本农觉着法一更易于理解
 */
function outRepeat(a){
  var hash=[],arr=[];
  for (var i = 0,elem;(elem=a[i])!=null; i++) {
    if(!hash[elem]){
      arr.push(elem);
      hash[elem]=true;
    }
  }
  return arr;
}

function removeByValue(arr, val) {
  for(var i=0; i<arr.length; i++) {
    if(arr[i] == val) {
      arr.splice(i, 1);
      break;
    }
  }
  return arr;
}

function js_in_array(search,array){
    for(var i in array){
        if(array[i]==search){
            return true;
        }
    }
    return false;
}

/*  keep number or point  */
function fomatFloat(src,pos){  
    return Math.round(src*Math.pow(10, pos))/Math.pow(10, pos);
}

/*  keep point  */
function numFloat(src){  
    return fomatFloat(src,3);
}

function keep_num_comma(num) {
    if(!isNaN(num)){
        return num;
    }
    num = String(num);
    var last_index = num.lastIndexOf(',')
    res = num.substr(last_index+1)
    res = res.substr(0,res.length-1)
    return res;
}

function keep_num(num) {
	if(!isNaN(num)){
		return num;
	}
    num = String(num);
    num = num.replace(/(^\s*)|(\s*$)/g, "");
    num = num.replace(/[^\-\.\d]/g, '');
    return num;
}

function keep_float_comma(src) {
    src = keep_num_comma(src);
    src = parseFloat(src);
    src = isNaN(src)?0:src;
    return src;
}

function keep_float(src) {
    src = keep_num(src);
    src = parseFloat(src);
    src = isNaN(src)?0:src;
    return src;
}

function keep_float_fmt(src,pos) {
    src = keep_num(src);
    src = parseFloat(src);
    src = isNaN(src)?0:src;
    if(pos){
        pos = keep_num(pos);
    }else{
        pos = 2;
    }
    src = fomatFloat(src,pos);
    src = price_king(src);
    return src;
}

function price_unking(num) {
    if (isNaN(num) && typeof(num) == 'string') {
        var x = num.split(',');
        return parseFloat(x.join(""));
    } else {
        return num;
    }
}

// console.log(number_format(2, 2, ".", ","))//"2.00"
// console.log(number_format(3.7, 2, ".", ","))//"3.70"
// console.log(number_format(3, 0, ".", ",")) //"3"
// console.log(number_format(9.0312, 2, ".", ","))//"9.03"
// console.log(number_format(9.00, 2, ".", ","))//"9.00"
// console.log(number_format(39.715001, 2, ".", ",", "floor")) //"39.71"
// console.log(number_format(9.7, 2, ".", ","))//"9.70"
// console.log(number_format(39.7, 2, ".", ","))//"39.70"
// console.log(number_format(9.70001, 2, ".", ","))//"9.71"
// console.log(number_format(39.70001, 2, ".", ","))//"39.71"
// console.log(number_format(9996.03, 2, ".", ","))//"9996.03"
// console.log(number_format(1.797, 3, ".", ",", "floor"))//"1.797"
function number_format(number, decimals, dec_point, thousands_sep,roundtag) {
    /*
    * 参数说明：
    * number：要格式化的数字
    * decimals：保留几位小数
    * dec_point：小数点符号
    * thousands_sep：千分位符号
    * roundtag:舍入参数，默认 "ceil" 向上取,"floor"向下取,"round" 四舍五入
    * */
    number = (number + '').replace(/[^0-9+-Ee.]/g, '');
    roundtag = roundtag || "ceil"; //"ceil","floor","round"
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
 
            var k = Math.pow(10, prec);
 
            return '' + parseFloat(Math[roundtag](parseFloat((n * k).toFixed(prec*2))).toFixed(prec*2)) / k;
        };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    var re = /(-?\d+)(\d{3})/;
    while (re.test(s[0])) {
        s[0] = s[0].replace(re, "$1" + sep + "$2");
    }
 
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

function fmoney(s, n) {
    /*
     * 参数说明：
     * s：要格式化的数字
     * n：保留几位小数
     * */
    n = n > 0 && n <= 20 ? n : 2;
    s = parseFloat((s + "").replace(/[^\d\.-]/g, "")).toFixed(n) + "";
    var l = s.split(".")[0].split("").reverse(),
        r = s.split(".")[1];
    t = "";
    for (i = 0; i < l.length; i++) {
        t += l[i] + ((i + 1) % 3 == 0 && (i + 1) != l.length ? "," : "");
    }
    return t.split("").reverse().join("") + "." + r;
}

function price_king(e) {
    if (e) {
        var k = e.toString().split('.')
        if (e.toString().indexOf('.') > 0) {
            var s = '.' + k[1]
        } else {
            var s = ''
        }
        return k[0].toString().replace(/\d{1,3}(?=(\d{3})+(\.\d*)?$)/g, '$&,') + s;
    } else {
        return e
    }
}

// cut point length
function cutPointNum(numstr,pos){
    pos = pos?pos:2;
    var n = keep_float(numstr);
    var lenArr = (n.toString()).split(".");
    var len = (lenArr[1])?lenArr[1].length:0;
    if(len>pos){
        var re = new RegExp("^\\d+(?:\\.\\d{0,"+pos+"})?","");
        n = (n.toString()).match(re);
    }
    return n;
}

// check and cut point length
function checkCutPointNum(numstr,pos){
    pos = pos?pos:2;
    var n = keep_float(numstr);
    var lenArr = (n.toString()).split(".");
    var len = (lenArr[1])?lenArr[1].length:0;
    if(len>pos){
        var re = new RegExp("^\\d+(?:\\.\\d{0,"+pos+"})?","");
        n = (n.toString()).match(re);
    }
    var ret = {};
    ret.iscut = (len>pos)?1:0;
    ret.num = keep_float(n);
    ret.old = keep_float(numstr);
    return ret;
}


