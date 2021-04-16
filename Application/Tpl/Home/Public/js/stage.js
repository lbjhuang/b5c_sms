var _hmt = window.hmt || [];
if (typeof JSON !== "object") {
    JSON = {}
}
(function () {
    function f(n) {
        return n < 10 ? "0" + n : n
    }

    if (typeof Date.prototype.toJSON !== "function") {
        Date.prototype.toJSON = function () {
            return isFinite(this.valueOf()) ? this.getUTCFullYear() + "-" + f(this.getUTCMonth() + 1) + "-" + f(this.getUTCDate()) + "T" + f(this.getUTCHours()) + ":" + f(this.getUTCMinutes()) + ":" + f(this.getUTCSeconds()) + "Z" : null
        };
        String.prototype.toJSON = Number.prototype.toJSON = Boolean.prototype.toJSON = function () {
            return this.valueOf()
        }
    }
    var cx, escapable, gap, indent, meta, rep;

    function quote(string) {
        escapable.lastIndex = 0;
        return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
            var c = meta[a];
            return typeof c === "string" ? c : "\\u" + ("0000" + a.charCodeAt(0).toString(16)).slice(-4)
        }) + '"' : '"' + string + '"'
    }

    function str(key, holder) {
        var i, k, v, length, mind = gap, partial, value = holder[key];
        if (value && typeof value === "object" && typeof value.toJSON === "function") {
            value = value.toJSON(key)
        }
        if (typeof rep === "function") {
            value = rep.call(holder, key, value)
        }
        switch (typeof value) {
            case"string":
                return quote(value);
            case"number":
                return isFinite(value) ? String(value) : "null";
            case"boolean":
            case"null":
                return String(value);
            case"object":
                if (!value) {
                    return "null"
                }
                gap += indent;
                partial = [];
                if (Object.prototype.toString.apply(value) === "[object Array]") {
                    length = value.length;
                    for (i = 0; i < length; i += 1) {
                        partial[i] = str(i, value) || "null"
                    }
                    v = partial.length === 0 ? "[]" : gap ? "[\n" + gap + partial.join(",\n" + gap) + "\n" + mind + "]" : "[" + partial.join(",") + "]";
                    gap = mind;
                    return v
                }
                if (rep && typeof rep === "object") {
                    length = rep.length;
                    for (i = 0; i < length; i += 1) {
                        if (typeof rep[i] === "string") {
                            k = rep[i];
                            v = str(k, value);
                            if (v) {
                                partial.push(quote(k) + (gap ? ": " : ":") + v)
                            }
                        }
                    }
                } else {
                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = str(k, value);
                            if (v) {
                                partial.push(quote(k) + (gap ? ": " : ":") + v)
                            }
                        }
                    }
                }
                v = partial.length === 0 ? "{}" : gap ? "{\n" + gap + partial.join(",\n" + gap) + "\n" + mind + "}" : "{" + partial.join(",") + "}";
                gap = mind;
                return v
        }
    }

    if (typeof JSON.stringify !== "function") {
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
        meta = {"\b": "\\b", "\t": "\\t", "\n": "\\n", "\f": "\\f", "\r": "\\r", '"': '\\"', "\\": "\\\\"};
        JSON.stringify = function (value, replacer, space) {
            var i;
            gap = "";
            indent = "";
            if (typeof space === "number") {
                for (i = 0; i < space; i += 1) {
                    indent += " "
                }
            } else {
                if (typeof space === "string") {
                    indent = space
                }
            }
            rep = replacer;
            if (replacer && typeof replacer !== "function" && (typeof replacer !== "object" || typeof replacer.length !== "number")) {
                throw new Error("JSON.stringify")
            }
            return str("", {"": value})
        }
    }
    if (typeof JSON.parse !== "function") {
        cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
        JSON.parse = function (text, reviver) {
            var j;

            function walk(holder, key) {
                var k, v, value = holder[key];
                if (value && typeof value === "object") {
                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v
                            } else {
                                delete value[k]
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value)
            }

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return "\\u" + ("0000" + a.charCodeAt(0).toString(16)).slice(-4)
                })
            }
            if (/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, "@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, "]").replace(/(?:^|:|,)(?:\s*\[)+/g, ""))) {
                j = eval("(" + text + ")");
                return typeof reviver === "function" ? walk({"": j}, "") : j
            }
            throw new SyntaxError("JSON.parse")
        }
    }
}());
(function (win, doc) {
    Date.prototype.Format = function (fmt) {
        var o = {
            "M+": this.getMonth() + 1,
            "d+": this.getDate(),
            "h+": this.getHours(),
            "m+": this.getMinutes(),
            "s+": this.getSeconds(),
            "q+": Math.floor((this.getMonth() + 3) / 3),
            "S": this.getMilliseconds()
        };
        if (/(y+)/.test(fmt)) {
            fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length))
        }
        for (var k in o) {
            if (new RegExp("(" + k + ")").test(fmt)) {
                fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)))
            }
        }
        return fmt
    };
    var _BASE_HOST = document.domain;
    var _TF = {t: "t", f: "f"};
    var _TTL = 24 * 365;
    var _CPSExpirationHour = 24;
    var _StatAccount = {
        baidu: {all: "4ddcb21e9c5f4053cb52fcfb8ea97ae8", stage: "4ddcb21e9c5f4053cb52fcfb8ea97ae8"},
        defaultSiteId: "stage",
        currentSiteId: function () {
            var site_id = this.defaultSiteId;
            var hostname = doc.location.hostname;
            if (hostname) {
                var hn = hostname.split(".");
                if (hn.length >= 3) {
                    hn.splice(-2, 2);
                    site_id = hn.join(".")
                }
            }
            return site_id
        }
    };
    var _currentSiteId = _StatAccount.currentSiteId();
    var _isIE = (navigator.appName == "Microsoft Internet Explorer");
    _Util = {
        guid: function () {
            return "xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx".replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0, v = c == "x" ? r : (r & 3 | 8);
                return v.toString(16)
            })
        }, dimension: function () {
            return screen.width + "*" + screen.height
        }, currentTimestamp: function () {
            return new Date().getTime()
        }, contains: function (a, obj) {
            var i = a.length;
            while (i--) {
                if (a[i] === obj) {
                    return true
                }
            }
            return false
        }, href: function (url) {
            if (!url || url.search(/^javascript:/) != -1) {
                return ""
            }
            if (url.search(/^mailto:/) != -1) {
                return ""
            }
            if (url.search(/^\/\//) != -1) {
                return win.location.protocol + url
            }
            if (url.search(/:\/\//) != -1) {
                return url
            }
            if (url.search(/^\//) != -1) {
                return win.location.origin + url
            }
            var base = win.location.href.match(/(.*\/)/)[0];
            return base + url
        }, on: function (el, type, fn) {
            if (_isIE) {
                el.attachEvent("on" + type, fn)
            } else {
                el.addEventListener(type, fn, false)
            }
        }, trim: function (value) {
            if (!value) {
                return ""
            }
            if (String.prototype.trim) {
                return value.trim()
            } else {
                return value.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g, "").replace(/\s+/g, " ")
            }
        }, gup: function (name, url) {
            var name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regexS = "[\\?&]" + name + "=([^&#]*)";
            var regex = new RegExp(regexS);
            if (!url) {
                url = win.location.href
            }
            var results = regex.exec(url);
            return results == null ? "" : results[1]
        }, addJsByUrl: function (url, id) {
            var parent = doc.createElement("div");
            parent.style["display"] = "none";
            if (id) {
                parent.id = id
            }
            var body = doc.getElementsByTagName("body")[0];
            body.appendChild(parent);
            var as = doc.createElement("script");
            as.type = "text/javascript";
            as.async = 1;
            as.src = url;
            parent.appendChild(as)
        }, getCookie: function (key) {
            return Cookie.get(key)
        }, actuateLink: function (link) {
            var allowDefaultAction = true;
            if (link.click) {
                link.click();
                return
            } else {
                if (doc.createEvent) {
                    var e = doc.createEvent("MouseEvents");
                    e.initEvent("click", true, true);
                    allowDefaultAction = link.dispatchEvent(e)
                }
            }
            if (allowDefaultAction) {
                var f = doc.createElement("form");
                f.action = link.href;
                doc.body.appendChild(f);
                f.submit()
            }
        }, random: function () {
            return Math.random()
        }, randomArbitrary: function (min, max) {
            return Math.random() * (max - min) + min
        }, randomInt: function (min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min
        }, stripTag: function (html) {
            html = html.replace(/<[^>]*>/g, "");
            var div = document.createElement("div");
            div.textContent = html;
            return div.innerHTML
        }, checkParents: function (node, string, attr) {
            var str = string.replace(/[.]/g, "");
            str = str.replace(/[#]/g, "");
            if (!node.parentNode) {
                return false
            }
            if (attr === "data-attr") {
                if (!node.getAttribute("data-attr")) {
                    return this.checkParents(node.parentNode, string, attr)
                } else {
                    if (node.getAttribute("data-attr") === string) {
                        return node
                    } else {
                        if (string === "all" && node.getAttribute("data-attr")) {
                            return node.getAttribute("data-attr")
                        } else {
                            return false
                        }
                    }
                }
            }
            switch (string[0]) {
                case".":
                    if (node.parentNode.className === undefined) {
                        return false
                    }
                    if (node.parentNode.className.indexOf(str) >= 0) {
                        return node.parentNode
                    } else {
                        return this.checkParents(node.parentNode, string)
                    }
                    break;
                case"#":
                    if (!node.parentNode.id === undefined) {
                        return false
                    }
                    if (node.parentNode.id.indexOf(str) >= 0) {
                        return node.parentNode
                    } else {
                        return this.checkParents(node.parentNode, string)
                    }
                    break;
                default:
                    if (!node.parentNode.nodeName === undefined) {
                        return false
                    }
                    if (node.parentNode.nodeName.toLowerCase().indexOf(str) >= 0) {
                        return node.parentNode
                    } else {
                        return this.checkParents(node.parentNode, string)
                    }
            }
        },getParents:function(el, parentSelector) {
            // If no parentSelector defined will bubble up all the way to *document*
            if (parentSelector === undefined) {
                parentSelector = document;
            }

            var parents = [];
            var p = el.parentNode;

            while (p !== parentSelector) {

                var o = p;
                parents.push(o);
                p = o.parentNode;
            }
            parents.push(parentSelector); // Push that parentSelector you wanted to stop at
            return parents;
        },include:function(el,str){
            if(el.className.indexOf(str) !== -1){
                return true
            }
            var nodeArr = this.getParents(el);
            for(var x = 0; x<nodeArr.length;x++){
                if(nodeArr[x].className){
                    if(nodeArr[x].className.indexOf(str) !== -1){
                        return true
                    }
                }
            }
            return false
        }
    };
    var _isIFRAME = (self != top);
    var _isB5T = function () {
        var _b5t = doc.getElementById("b5mmain");
        return _b5t && _b5t.tagName == "SCRIPT"
    };
    var _uuid = function () {
        var uuid = Cookie.get("userId");
        if (uuid) {
            return uuid
        }
        return ""
    };
    var _b5tid = function () {
        var tid = Cookie.get("b5tuid");
        if (!tid) {
            var location = window.location.href;
            if (location.indexOf("#") > 0) {
                var str = location.substring(location.indexOf("#") + 1);
                var ss = str.split("|");
                for (var i = 0; i < ss.length; i++) {
                    var s1 = ss[i];
                    var ss2 = s1.split("=");
                    if (ss2[0] == "uuid") {
                        tid = ss2[1];
                        break
                    }
                }
            }
        }
        return tid
    };
    var _isLogin = function () {
        var login = Cookie.get("userId");
        return login ? 1 : 0
    };
    var _ab = function () {
        var ab = Cookie.get("_b5mab");
        return ab || ""
    };
    var _Request = {
        send: function (url, image_id) {
            var _this = this;
            if (!url || typeof url !== "string") {
                return
            }
            var img = doc.getElementById("stat_image");
            if (!img) {
                var img = doc.createElement("img");
                doc.body.appendChild(img);
                img.id = "stat_image";
                img.style.display = "none"
            }
            img.src = url
        }
    };
    var _hook = {
        hookInit:function(){
            _hook.hookAJAX()
        },
        hookAJAX:function() {
            XMLHttpRequest.prototype.nativeOpen = XMLHttpRequest.prototype.open;
            var customizeOpen = function (method, url, async, user, password) {
                this.nativeOpen(method, url, async, user, password);
                this._time = Date.now();
                this.bool = false;
                _Util.on(this,'readystatechange',function(e){
                    if(this.bool === true){
                        return
                    }
                    this.bool = true
                    var time = Date.now() - this._time
                    var tm = {
                        action:{
                            url:url,
                            time:time
                        }
                    }
                    var data = _B5mStat.data();
                    data.ad = 109
                    data.tm = JSON.stringify(tm)
                    _B5mStat.send(data)
                })
                /*this.onreadystatechange = function(){
                    time = Date.now() - time
                    var tm = {
                        action:{
                            url:url,
                            time:time
                        }
                    }
                    var data = _B5mStat.data();
                    data.tm = JSON.stringify(tm)
                    _B5mStat.send(data)
                }*/
            };

            XMLHttpRequest.prototype.open = customizeOpen;
        },
        hookImg:function () {
            var property = Object.getOwnPropertyDescriptor(Image.prototype, 'src');
            var nativeSet = property.set;

            function customiseSrcSet(url) {
                nativeSet.call(this, url);
            }
            Object.defineProperty(Image.prototype, 'src', {
                set: customiseSrcSet,
            });
        },
        hookOpen:function () {
            var nativeOpen = window.open;
            window.open = function (url) {
                // do something
                nativeOpen.call(this, url);
            };
        },

        hookFetch:function () {
            var fet = Object.getOwnPropertyDescriptor(window, 'fetch')
            Object.defineProperty(window, 'fetch', {
                value: function (a, b, c) {
                    // do something
                    return fet.value.apply(this, args)
                }
            })
        }
    }
    var Cookie = {
        get: function (c_name) {
            if (document.cookie.length > 0) {
                c_start = document.cookie.indexOf(c_name + "=");
                if (c_start != -1) {
                    c_start = c_start + c_name.length + 1;
                    c_end = document.cookie.indexOf(";", c_start);
                    if (c_end == -1) {
                        c_end = document.cookie.length
                    }
                    return unescape(document.cookie.substring(c_start, c_end))
                }
            }
            return ""
        }, set: function (key, value, ttl, path, domain, secure) {
            cookie = [key + "=" + escape(value), "path=" + ((!path || path == "") ? "/" : path), "domain=" + ((!domain || domain == "") ? win.location.hostname : domain)];
            if (ttl) {
                cookie.push("expires=" + this.hoursToExpireDate(ttl))
            }
            if (secure) {
                cookie.push("secure")
            }
            return doc.cookie = cookie.join("; ")
        }, unset: function (key, path, domain) {
            path = (!path || typeof path != "string") ? "" : path;
            domain = (!domain || typeof domain != "string") ? "" : domain;
            if (this.get(key)) {
                this.set(key, "", "Thu, 01-Jan-70 00:00:01 GMT", path, domain)
            }
        }, hoursToExpireDate: function (ttl) {
            if (parseInt(ttl) == "NaN") {
                return ""
            } else {
                now = new Date();
                now.setTime(now.getTime() + (parseInt(ttl) * 60 * 60 * 1000));
                return now.toGMTString()
            }
        }, test: function () {
            this.set("b49f729efde9b2578ea9f00563d06e57", "true");
            if (this.get("b49f729efde9b2578ea9f00563d06e57") == "true") {
                this.unset("b49f729efde9b2578ea9f00563d06e57");
                return true
            }
            return false
        }
    };
    WSAjax = {
        init: function (_url, _params, _callback) {
            if (_params) {
                var s = [];
                for (var k in _params) {
                    s.push(k + "=" + _params[k])
                }
                var p = _url.indexOf("?") >= 0 ? "&" + s.join("&") : "?" + s.join("&");
                _url = _url + p
            }
            this.url = _url;
            this.callback = _callback;
            this.connect()
        }, connect: function () {
            var script_id = null;
            var script = document.createElement("script");
            script.setAttribute("type", "text/javascript");
            script.setAttribute("src", this.url);
            script.setAttribute("id", "xss_ajax_script");
            script_id = document.getElementById("xss_ajax_script");
            if (script_id) {
                document.getElementsByTagName("head")[0].removeChild(script_id)
            }
            document.getElementsByTagName("head")[0].appendChild(script)
        }, process: function (data) {
            this.callback(data)
        }
    };
    var _trackEvent = function (callback) {
        var body = doc.getElementsByTagName("body")[0];
        _Util.on(body, "click", function (e) {
            var target = e.target ? e.target : e.srcElement;
            if (!e) {
                var e = win.event
            }
            var target = e.target ? e.target : e.srcElement;
            if (target.nodeType == 3) {
                target = target.parentNode
            }
            var tname = target.tagName.toLowerCase();
            if (tname == "input") {
                tname = tname + ":" + target.type
            }
            var attr = target.getAttribute("mini-attr");
            if (attr) {
                callback(tname, "click", attr, "")
            } else {
                callback(tname, "click", win.location.href, "")
            }
        })
    };
    var _BaiduStat = {
        create: function (accountNo) {
            var _bdhmProtocol = (("https:" == doc.location.protocol) ? " https://" : " http://");
            var url = _bdhmProtocol + "hm.baidu.com/h.js?" + accountNo;
            _Util.addJsByUrl(url)
        }, track: function (accountNo) {
            _hmt.push(["_setAccount", accountNo]);
            _trackEvent(function (category, action, label, value) {
                if (category == "a") {
                    var hmsr = _Util.gup("hmsr");
                    if (hmsr) {
                        category += "_" + hmsr
                    }
                }
                _hmt.push(["_trackEvent", category, action, label, value])
            })
        }, init: function () {
            var nobaidu = document.getElementById("no_baidu_stat");
            if (!nobaidu) {
                if (_StatAccount.baidu.all) {
                    this.create(_StatAccount.baidu.all)
                }
                var accountNo = _StatAccount.baidu[_currentSiteId];
                if (accountNo) {
                    this.create(accountNo);
                    this.track(accountNo)
                }
            }
        }
    };
    _timeData = {
        init:function(){
            _Util.on(window,'load',function(){
                _timeData.getAllTime()
            })
        },
        getAllTime: function(){
            var tm = {
                dns:_timeData.DNS(),
                request:_timeData.request(),
                dom:_timeData.dom(),
                whiteScreen:_timeData.whiteScreen(),
                onload:_timeData.onload(),
            }
            var data = _B5mStat.data();
            data.ad = 109
            data.tm = JSON.stringify(tm);
            _B5mStat.send(data)
        },
        DNS: function(){
            return window.performance.timing.domainLookupEnd - window.performance.timing.domainLookupStart
        },
        request: function(){
            return window.performance.timing.responseEnd - window.performance.timing.responseStart
        },
        dom: function(){
            return  window.performance.timing.domComplete - window.performance.timing.domInteractive
        },
        whiteScreen: function(){
            return window.performance.timing.responseStart - window.performance.timing.navigationStart
        },
        onload: function(){
            return window.performance.timing.loadEventEnd - window.performance.timing.navigationStart
        }
    }
    var fu = ''
    if(localStorage.getItem('fromFrame')){
        fu = localStorage.getItem('fromFrame')
        localStorage.removeItem('fromFrame')
    }
    var _B5mStat = {
        actionCodes: {
            body: 101,
            div: 102,
            a: 103,
            img: 104,
            input_text: 105,
            input_submit: 106,
            others: 100,
            span: 107,
            load: 108,
            input_text_enter: 109,
            unload: 200,
            fu:fu
        }, data: function (dstl, ad, av, mt, dx, dy, wx, wy, pv, vv) {
            var sl = document.documentElement.scrollLeft || document.body.scrollLeft || 0;
            dx = sl + dx;
            var st = document.documentElement.scrollTop || document.body.scrollTop || 0;
            dy = st + dy;
            var dl = win.location.href;
            if (ad == this.actionCodes.load) {
                var input = doc.getElementById("is_mini");
                if (input) {
                    var version_value = Cookie.get("version_value");
                    var _param = null;
                    if (version_value == 1) {
                        _param = "mini=1"
                    } else {
                        _param = "mini=2"
                    }
                    if (_param) {
                        if (dl.indexOf("?") > 0) {
                            dl = dl + "&" + _param
                        } else {
                            dl = dl + "?" + _param
                        }
                    }
                }
            }

            return {
                uid: _uuid(),
                cid: Cookie.get("cookieId"),
                at: this.activeTimes(),
                dl: dl,
                dstl: dstl || "",
                dr: doc.referrer,
                lt: 1002,
                ad: ad || this.actionCodes.load,
                av: av || "",
                mt: mt || "",
                dx: dx || "",
                dy: dy || "",
                wx: wx || "",
                wy: wy || "",
                sr: _Util.dimension(),
                ct: _Util.currentTimestamp(),
                ff: _isIFRAME ? _TF.t : _TF.f,
                b5t: _isB5T(),
                pv: pv || "",
                il: _isLogin(),
                tid: _b5tid(),
                sid: Cookie.get("sessionId"),
                ab: _ab(),
                mps: Cookie.get("_gtraffic") || "." || Cookie.get("_gpagelist"),
                bf: Cookie.get("_gflag"),
                fb: Cookie.get("_gfb"),
                site: Cookie.get("webSiteCode"),
                tm:'{}',
                fu:fu
            }
        }, activeTimes: function () {
            var host = win.location.hostname;
            var excludeSiteIds = ["staticcdn"];
            var siteId = host.replace(".gshopper.com", "");
            if (_Util.contains(excludeSiteIds, siteId)) {
                return ""
            }
            var active_value = Cookie.get("_active_value");
            if (active_value) {
                active_value = JSON.parse(active_value)
            } else {
                active_value = {}
            }
            if (!active_value[siteId]) {
                active_value[siteId] = {l: 0, t: 0}
            }
            var cookie_id = Cookie.get("cookie_id");
            var active_times_key = cookie_id + "-" + host + "-activeTimes";
            var last_active_time_key = cookie_id + "-" + host + "-lastActiveTime";
            var activeTimes = Cookie.get(active_times_key);
            var lastActiveTime = Cookie.get(last_active_time_key);
            if (activeTimes && lastActiveTime) {
                active_value[siteId].l = lastActiveTime;
                active_value[siteId].t = activeTimes;
                Cookie.unset(last_active_time_key, "/", _BASE_HOST);
                Cookie.unset(active_times_key, "/", _BASE_HOST)
            }
            var currentTimestamp = _Util.currentTimestamp();
            if (active_value[siteId].l) {
                if (currentTimestamp - active_value[siteId].l > 12 * 3600 * 1000) {
                    active_value[siteId].t++
                }
                active_value[siteId].l = currentTimestamp
            } else {
                active_value[siteId].l = currentTimestamp;
                active_value[siteId].t = 1
            }
            Cookie.set("_active_value", JSON.stringify(active_value), _TTL, "/", _BASE_HOST);
            return active_value[siteId].t
        }, setDataAttr: function (target, data) {
            if (target) {
                var attr = _Util.checkParents(target, "all", "data-attr");
                if (attr) {
                    data.pv = attr
                }
                var tag = target.tagName.toLowerCase();
                if (tag == "span" || tag == "font" || tag == "dd" || tag == "b" || tag == "strong" || tag == "img") {
                    var pn = target;
                    while (pn && pn.tagName != "BODY") {
                        var attr = pn.getAttribute("data-attr");
                        if (attr) {
                            data.pv = attr;
                            break
                        } else {
                            pn = pn.parentNode
                        }
                    }
                }
            }
        }, track: function () {
            var _this = this;
            var body = doc.getElementsByTagName("body")[0];
            var callback = function (e) {
                var target = e.target ? e.target : e.srcElement;
                if (!e) {
                    var e = win.event
                }
                var target = e.target ? e.target : e.srcElement;
                if (target.className.indexOf("page-container") >= 0) {
                    return
                }
                if (target.nodeType == 3) {
                    target = target.parentNode
                }
                var tname = target.tagName;
                if (tname == "INPUT") {
                    tname = tname + "_" + target.type
                }
                tname = tname.toLowerCase();
                var data = _this.data("", "", "", e.which, e.clientX, e.clientY, e.screenX, e.screenY);
                data.sr = _Util.dimension();
                data.ta = tname;
                var attrTarget = target;
                if (_this.actionCodes[tname]) {
                    data.ad = _this.actionCodes[tname];
                    if (tname == "a") {
                        data.dstl = _Util.href(target.href);
                        data.av = target.innerHTML
                    } else {
                        if (tname == "img" || tname == "span") {
                            data.av = target.innerHTML;
                            var pn = target.parentNode;
                            if (pn) {
                                if (pn.tagName.toLowerCase() == "a") {
                                    data.dstl = pn.href;
                                    attrTarget = pn
                                }
                            }
                        } else {
                            if (tname == "input_submit") {
                                data.av = target.value
                            }
                        }
                    }
                    if (data.av) {
                        data.av = _Util.trim(data.av);
                        if (data.av.length > 40) {
                            data.av = data.av.substring(0, 40)
                        }
                    }
                } else {
                    data.ad = _this.actionCodes["others"];
                    if (tname == "b" || tname == "em") {
                        data.av = target.innerHTML;
                        var pn = target.parentNode;
                        if (pn) {
                            if (pn.tagName.toLowerCase() == "a") {
                                data.dstl = pn.href;
                                attrTarget = pn;
                                data.av = attrTarget.innerHTML
                            }
                        }
                    } else {
                        data.av = tname
                    }
                }
                if (_Util.checkParents(target, "1011", "data-attr") || _Util.checkParents(target, "1015", "data-attr")) {
                    var length = _Util.checkParents(e.target, ".cart-item-main").querySelector(".cart-item-params").childNodes.length;
                    var dom = _Util.checkParents(e.target, ".cart-item-main").querySelector(".cart-item-params").childNodes[length - 1];
                    var num = dom.innerText.substring(dom.innerText.indexOf(":") + 1);
                    var attr = "";
                    if (_Util.checkParents(target, "1011", "data-attr")) {
                        attr = "1011"
                    }
                    if (_Util.checkParents(target, "1015", "data-attr")) {
                        attr = "1015"
                    }
                    data.pv = attr;
                    data.b5t = num
                }
                if (_Util.checkParents(target, "1014", "data-attr") || _Util.checkParents(target, "1013", "data-attr")) {
                    var href = _Util.checkParents(e.target, ".wish-item-container").querySelector("a").getAttribute("href");
                    href = href.split("/")[href.split("/").length - 1];
                    href = href.split("?")[0];
                    var attr = "";
                    if (_Util.checkParents(target, "1014", "data-attr")) {
                        attr = "1011"
                    }
                    if (_Util.checkParents(target, "1013", "data-attr")) {
                        attr = "1013"
                    }
                    data.pv = attr;
                    data.b5t = href
                }
                if (_Util.checkParents(target, ".channel-content")) {
                    var href = _Util.checkParents(e.target, "a").getAttribute("href");
                    data.dstl = href
                }
                data.traffic = data.mps = _Spc.spm(attrTarget);


                if(tname === 'a' || tname === 'button'){
                    data.pg = '10004'
                }
                if(
                    _Util.include(e.target,'number') ||
                    _Util.include(e.target,'el-icon-arrow-left') ||
                    _Util.include(e.target,'el-icon-arrow-right')
                ){
                    data.pg = '10001'
                }
                if(
                    _Util.include(e.target,'tab-item') ||
                    _Util.include(e.target,'channel-item') ||
                    _Util.include(e.target,'active') ||
                    _Util.include(e.target,'status_check')||
                    _Util.include(e.target,'type_check')||
                    _Util.include(e.target,'contain-remark') ||
                    _Util.include(e.target,'contain-error') ||
                    _Util.include(e.target,'status_check') ||
                    _Util.include(e.target,'ship_status')
                ){
                    data.pg = '10002'
                }
                if(
                    _Util.include(e.target,'btn-search') ||
                    _Util.include(e.target,'btn-reset') ||
                    _Util.include(e.target,'border-btn')
                ){
                    data.pg = '10003'
                }
                if(
                    _Util.include(e.target,'el-button') ||
                    _Util.include(e.target,'button')
                ){
                    data.pg = '10004'
                }
                if(_Util.include(e.target,'el-select-dropdown__item')){
                    data.pg = '10005'
                }
                _this.setDataAttr(attrTarget, data);
                _this.send(data)
            };
            var inputs = doc.getElementsByTagName("input");
            if (inputs) {
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    if (input.type && input.type.toLowerCase() == "text") {
                        _Util.on(input, "keypress", function (e) {
                            if (e.keyCode == 13) {
                                var data = _this.data("", "", "", e.which, e.clientX, e.clientY, e.screenX, e.screenY);
                                data.sr = _Util.dimension();
                                data.ad = _this.actionCodes["input_text_enter"];
                                data.av = input.value;
                                var attr = input.getAttribute("data-attr");
                                if (attr) {
                                    data.pv = attr
                                }
                                _this.send(data)
                            }
                        })
                    }
                }
            }
            _Util.on(body, "mousedown", callback)
        }, send: function (data) {
            var s = [];
            for (var k in data) {
                if (k != "traffic") {
                    var ss = k + "=" + encodeURIComponent(data[k]);
                    s.push(ss)
                }
            }
            var params = s.join("&");
            var url = "https://logs.gshopper.com/gweb/_utm.gif?" + params;
            _Request.send(url, "stat_image")
        }, addEvent: function (actionId, actionValue) {
            this.send(this.data("", actionId, actionValue))
        }, init: function () {
            var nob5m = document.getElementById("no_b5m_stat");
            if (!nob5m) {
                var cid = Cookie.get("cookieId");
                if (!cid) {
                    Cookie.set("cookieId", _Util.guid(), _TTL, "/", _BASE_HOST)
                }
                var sid = Cookie.get("sessionId");
                if (!sid) {
                    Cookie.set("sessionId", _Util.guid(), 0, "/", _BASE_HOST)
                }
                this.track();
                localStorage.setItem("222", JSON.stringify(this.data("", this.actionCodes.load)));
                this.send(this.data("", this.actionCodes.load))
            }
        }
    };
    var _Spc = {
        domainCode: (function () {
            var metas = document.getElementsByTagName("meta");
            if (metas) {
                for (var i = 0; i < metas.length; i++) {
                    if (metas[i] && metas[i].name == "data-mps") {
                        return metas[i].content
                    }
                }
            }
            return 0
        })(), channel: (function () {
            var hmsr = _Util.gup("hmsr") || "";
            var hmmd = _Util.gup("hmmd") || "";
            var hmpl = _Util.gup("hmpl") || "";
            var hmkw = _Util.gup("hmkw") || "";
            var hmci = _Util.gup("hmci") || "";
            var _ch = [hmsr, hmmd, hmpl, hmkw, hmci].join("_");
            var _mps = _Util.gup("mps");
            if (_ch == "____") {
                if (_mps) {
                    var y = _mps.split(".");
                    _ch = y.length > 0 ? y[0] : ""
                } else {
                    var _tr = Cookie.get("_gtraffic");
                    var from_b5m = doc.referrer && doc.referrer.indexOf("gshopper.com") >= 0;
                    if (_tr && from_b5m) {
                        _s = _tr.split(".");
                        _ch = _s.length > 0 ? _s[0] : "____"
                    }
                }
            }
            return _ch
        })(), trackEvent: function (callback) {
            var body = document.getElementsByTagName("body")[0];
            _Util.on(body, "click", function (e) {
                if (!e) {
                    var e = win.event
                }
                var target = e.target ? e.target : e.srcElement;
                if (target.nodeType == 3) {
                    target = target.parentNode
                }
                var tname = target.tagName;
                if (tname == "INPUT") {
                    tname = tname + "_" + target.type
                }
                tname = tname.toLowerCase();
                if (tname == "img" || tname == "span") {
                    target = target.parentNode;
                    if (target) {
                        tname = target.tagName.toLowerCase()
                    }
                }
                if (tname == "a" && (target.href.indexOf("taobao.com") < 0) && (target.href.indexOf("tmall.com") < 0)) {
                    callback(target)
                }
            })
        }, spm: function (linkTarget) {
            var pagelist = "x";
            var channel = this.channel;
            var domainCode = this.domainCode;
            var pageCode = "x";
            var moduleCode = "x";
            var positionCode = "x";
            if (linkTarget) {
                if (linkTarget.tagName != "BODY") {
                    positionCode = linkTarget.getAttribute("data-mps") ? linkTarget.getAttribute("data-mps") : "x";
                    if (positionCode == "x") {
                        var pnp = linkTarget.parentNode;
                        while (pnp && pnp.tagName) {
                            var attrp = pnp.getAttribute("data-mps");
                            if (attrp && pnp.tagName == "A") {
                                positionCode = attrp;
                                break
                            } else {
                                pnp = pnp.parentNode
                            }
                        }
                    }
                }
                if (linkTarget.tagName == "DIV" && linkTarget.getAttribute("data-mps")) {
                    moduleCode = linkTarget.getAttribute("data-mps") ? linkTarget.getAttribute("data-mps") : "x"
                } else {
                    var pn = linkTarget.parentNode;
                    while (pn && pn.tagName && pn.tagName != "BODY") {
                        var attr = pn.getAttribute("data-mps");
                        if (attr) {
                            moduleCode = attr;
                            break
                        } else {
                            pn = pn.parentNode
                        }
                    }
                }
            }
            var body = document.getElementsByTagName("body")[0];
            var pageCode = body.getAttribute("data-mps") ? body.getAttribute("data-mps") : "x";
            var pageList = Cookie.get("_gpagelist");
            return [channel, domainCode, pageCode, moduleCode, positionCode, pageList].join(".")
        }, addSpm: function () {
            var _this = this;
            this.trackEvent(function (target) {
                var href = _Util.href(target.href);
                if (href) {
                    if (href && href.indexOf(_BASE_HOST) >= 0) {
                        href = href.replace(/[&|?]{0,}mps=[\w|\\.]+/, "");
                        var newHref = href;
                        if (href.indexOf("#") >= 0) {
                            var tmp = href.split("#");
                            if (tmp.length = 2) {
                                newHref = tmp[0] + (href.indexOf("?") >= 0 ? "&" : "?") + "mps=" + _this.channel + "#" + tmp[1]
                            }
                        } else {
                            newHref = href + (href.indexOf("?") >= 0 ? "&" : "?") + "mps=" + _this.channel
                        }
                        target.setAttribute("href", newHref)
                    }
                }
            })
        }, init: function () {
            var _this = this;
            var nospm = document.getElementById("nospm");
            if (!nospm) {
                var body = document.getElementsByTagName("body")[0];
                var pageCode = body.getAttribute("data-mps") ? body.getAttribute("data-mps") : "x";
                var pageList = Cookie.get("_gpagelist");
                if (pageList) {
                    if (pageList.indexOf(pageCode) < 0) {
                        pageList = [pageList, pageCode].join("-")
                    }
                } else {
                    pageList = pageCode
                }
                pageList = pageList.length >= 255 ? pageList.subString(pageList.length - 200, pageList.length) + "-CUT" : pageList;
                Cookie.set("_gpagelist", pageList, 0, "/", _BASE_HOST);
                traffic = _this.spm(document.getElementsByTagName("BODY")[0]);
                Cookie.set("_gtraffic", traffic, null, "/", _BASE_HOST)
            }
        }
    };
    var _B5MCPS = {
        init: function () {
            var b5mts = Cookie.get("_b5mts");
            var curts = (Date.parse(new Date()) / 1000);
            if (!b5mts) {
                Cookie.set("_b5mts", curts, _CPSExpirationHour, "/", _BASE_HOST)
            }
            var wf = (curts - b5mts >= 3600);
            var b5mflag = _Util.gup("b5mflag") || "";
            if (!b5mflag) {
                b5mflag = "organic"
            }
            var b5mfb = _Util.gup("b5mfb") || "";
            var lastb5mflag = Cookie.get("_gflag") || "";
            if (!lastb5mflag) {
                Cookie.set("_gflag", b5mflag, _CPSExpirationHour, "/", _BASE_HOST);
                Cookie.set("_gfb", b5mfb, _CPSExpirationHour, "/", _BASE_HOST);
                Cookie.set("_b5mts", curts, _CPSExpirationHour, "/", _BASE_HOST)
            } else {
                if (wf) {
                    Cookie.set("_gflag", b5mflag, _CPSExpirationHour, "/", _BASE_HOST);
                    Cookie.set("_gfb", b5mfb, _CPSExpirationHour, "/", _BASE_HOST);
                    Cookie.set("_b5mts", curts, _CPSExpirationHour, "/", _BASE_HOST)
                }
            }
        }
    };
    var _InitListen = {
        init: function () {
            this.onClose();
            this.onScroll();
            this.onPage();
            this.onClick();
            this.onLoad();
        }, onClose: function () {
            var timestamp = 0;
            _Util.on(window, "beforeunload", function () {
                timestamp = (new Date()).valueOf()
            });
            _Util.on(window, "unload", function () {
                var time = (new Date()).valueOf() - timestamp;
                if (time <= 3) {
                    _B5mStat.send(_B5mStat.data("", 200))
                } else {
                    var data = _B5mStat.data();
                    data.traffic = data.mps = _Spc.spm(document.body);
                    data.ad = 110;
                    localStorage.setItem("111", JSON.stringify(data));
                    _B5mStat.send(data)
                }
            })
        }, onScroll: function () {
            var scrollTimer = "";
            _Util.on(window, "scroll", function (e) {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function () {
                    var p = Math.ceil(getScrollTop() / getClientHeight());
                    p = p + 1;
                    _B5mStat.send(_B5mStat.data("", 209, p))
                }, 2000)
            });

            function getClientHeight() {
                var clientHeight = 0;
                if (document.body.clientHeight && document.documentElement.clientHeight) {
                    var clientHeight = (document.body.clientHeight < document.documentElement.clientHeight) ? document.body.clientHeight : document.documentElement.clientHeight
                } else {
                    var clientHeight = (document.body.clientHeight > document.documentElement.clientHeight) ? document.body.clientHeight : document.documentElement.clientHeight
                }
                return clientHeight
            }

            function getScrollTop() {
                var scrollPos;
                if (window.pageYOffset) {
                    scrollPos = window.pageYOffset
                } else {
                    if (document.compatMode && document.compatMode != "BackCompat") {
                        scrollPos = document.documentElement.scrollTop
                    } else {
                        if (document.body) {
                            scrollPos = document.body.scrollTop
                        }
                    }
                }
                return scrollPos
            }
        }, onPage: function () {
        },onClick: function(){
            _Util.on(window, "click", function (e) {
                    localStorage.setItem('fromFrame',location.href)
            });
        },onLoad: function () {
            _Util.on(window, "load", function (e) {
                var inputs = document.querySelectorAll('input')
                for(var x = 0;x<inputs.length;x++){
                    _Util.on(inputs[x], "blur", function (e) {
                        var data = _B5mStat.data();
                        data.pg = 10006;
                        _B5mStat.send(data)
                    });
                }
            });
        }
    };
    Stat = {
        init: function () {
            _Spc.init();
            _B5mStat.init();
            _InitListen.init();
            _hook.hookInit();
            _timeData.init()
        }, addEvent: function (actionId, actionValue) {
            _B5mStat.addEvent(actionId, actionValue)
        }
    }
})(window, document);
Stat.init();