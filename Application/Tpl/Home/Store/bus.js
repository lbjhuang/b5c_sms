const bus = new Vue();
// 我们可以把这个对象挂载在原型对象上 方便使用
Vue.prototype.$bus = bus;