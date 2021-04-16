var arr=['company','vendor','warehouse']
var obj={
    'count':62,
    'page_count':"10",
}
Mock.mock('https://www.mockTest.com', {
    "page":{
        'count':12,
        'page_count':"10",
    },
    "data|12": [{
        "id|+1": 1,
        'vendorName|1':arr,
        'ourCompanyName|1':arr,
        "contractAmount|18-28": 25,
        "paymentAmount|18-28": 25,
        "storageAmount|18-28": 25,
        "amount|18-28": 25,
        "purchasingTeam|1":arr
    }]
})

