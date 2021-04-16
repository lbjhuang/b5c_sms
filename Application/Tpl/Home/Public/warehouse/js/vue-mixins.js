const mixins = {
    filters: {
        dateFormat(timestamp) {
            if (!timestamp) return timestamp;
            // 切换时区转换时间戳为日期时间
            return new Date(timestamp - new Date().getTimezoneOffset() * 60 * 1000).toJSON().substring(0, 19).replace('T', ' ');
        }
    }
}