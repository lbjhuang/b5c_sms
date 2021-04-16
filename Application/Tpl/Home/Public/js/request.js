const host = {
  stage: {
    erp: "",
    insight: "//insight.gshopper.stage.com/insight-backend",
    insightHttps: "//insightprod.gshopper.com/insight-backend/", // https 地址
  },
  prod: {
    erp: "",
    insight: "//insight.gshopper.com/insight-backend",
  },
  local: {
    erp: "",
    insight: "//insight.gshopper.stage.com/insight-backend",
  },
};

/**
 * @func
 * @param {string} url 请求路径
 * @param {object} params 请求参数
 * @param {string} project 项目参数,默认erp  erp|insight
 * @param {object} options 选填参数 暂时只有 {method: 'post', headers: {}, responseType: 'blob', loading: false(提示) } 请求方法
 *
 * @example 使用方式
 *  erp: request('index.php?m=finance&a=list', {a: 2})
 *  insight request('aaa/bbbb', {a: 2}, 'insight')
 *  需要支持其他平台可传递project修改，以及添加相关host
 */
function request(url, params = {}, project = "erp", options = {}) {
  let { headers, method = "post", responseType = 'json', timeout = 10000, loading = true } = options;
  // 主要限制参数不对，请求不知道请求到哪里去的问题
  if (!["erp", "insight"].includes(arguments[2])) {
    project = "erp";
  }
  if (
    !["get", "post", "head", "delete", "put", "patch"].includes(arguments[3])
  ) {
    method = "post";
  }
  let location = window.location.host;
  let env =
    location.indexOf("erp.gshopper.com") !== -1
      ? "prod"
      : (location.indexOf("erp.gshopper.stage.com") !== -1 || location.indexOf("erpstage.gshopper.com") !== -1)
      ? "stage"
      : "local";
  const protocol = window.location.protocol; 
  let baseURL = window.location.protocol  + host[env][project];
  if (project === 'insight' && env === 'stage' && protocol.includes('https')) {
    // insight接口的https环境要切换新地址  2021-03-17 tujin
    baseURL = window.location.protocol  + host[env]['insightHttps'];
  }
  const instance = axios.create({
    baseURL: baseURL,
    timeout: timeout,
    headers: {
      "Content-Type": "application/json;charset=utf-8",
    },
  });

  instance.interceptors.request.use((req) => {
    req.headers = Object.assign(req.headers, headers);
    if (req.method === "post") {
      req.params = {};
    }
    // 非erp平台需要传递请求来源，平台cookie
    if (project !== "erp") {
      req.headers['erp-cookie'] = "PHPSESSID=" + getCookie("PHPSESSID") + ";";
      req.headers["erp-req"] = true;
    }
    return req;
  });

  instance.interceptors.response.use(
    (response) => {
      const bus = new Vue();
      const result = response.data;
      if (response.status !== 200) {
        ELEMENT.Message.error(bus.$lang(result.msg));
      }
      // blob 等数据类型没有code等信息,直接返回
      if (responseType !== 'json') return result;
      if (
        result.success !== true &&
        result.code != 200 &&
        result.code !== 2000
        && loading
      ) {
        ELEMENT.Message.error(bus.$lang(result.msg));
      }
      return result;
    },
    (err) => {
      // ELEMENT.Message.error('网络错误,请重试！')
      ELEMENT.Message.error(err.message);
    }
  );

  return instance.request({
    url,
    method,
    data: params,
    responseType: responseType
  });
}
