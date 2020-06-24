$(function () {

    Date.prototype.format = function (format) {
        var date = {
            "M+": this.getMonth() + 1,
            "d+": this.getDate(),
            "h+": this.getHours(),
            "m+": this.getMinutes(),
            "s+": this.getSeconds(),
            "q+": Math.floor((this.getMonth() + 3) / 3),
            "S+": this.getMilliseconds()
        };
        if (/(y+)/i.test(format)) {
            format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
        }
        for (var k in date) {
            if (new RegExp("(" + k + ")").test(format)) {
                format = format.replace(RegExp.$1, RegExp.$1.length == 1
                    ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
            }
        }
        return format;
    };

    $.tbreport.highlight();
    $.tbreport.formateTimeStamp();
    $.tbreport.transferSec2Min();
});


(function ($) {

    $.tbreport = $.fn.tbreport = {
        highlight: function () {
            $.tbreport.antiFraud();
            $.tbreport.statisticSummary();
        },
        //统计概要
        statisticSummary: function() {
            $.tbreport.formatChangeTag("addrChangeTimes")
            $.tbreport.formatChangeTag("cityChangeTImes")

            $.tbreport.formatConsumeTag("oneyearConsumeTimes")
            $.tbreport.formatConsumeTag("petitionerConsumeTimes")
            $.tbreport.formatConsumeTag("huabeiConsumeTimes")
            $.tbreport.formatConsumeTag("footmarkSize")
            $.tbreport.formatConsumeTag("rechargeTimes")
        },
        //
        formatConsumeTag: function(id){
            var value = $("#"+id).text();
            if(value === '无'){
                return;
            }
            if(value === '没有' || value === '频繁'){
                $("#"+id).attr("class","tag warn")
            }else if(value === '异常'){
                $("#"+id).attr("class","tag danger")
            }else {
                $("#" + id).attr("class", "tag normal")
            }
        },
        //变化类标签
        formatChangeTag: function(id){
            var value = $("#"+id).text();
            if(value === '无'){
                return;
            }
            if(value === '异常'){
                $("#"+id).attr("class","tag danger")
            }else if(value === '频繁'){
                $("#"+id).attr("class","tag warn")
            }else {
                $("#"+id).attr("class","tag normal")
            }
        },
        formateTimeStamp: function () {
            $(".timestamp").each(function (index, element) {
                var time = $(this).text();
                $(this).text($.tbreport.getTimeStr(time));
            });
        },

        transferSec2Min: function () {
            $.tbreport.normalTime();
            $.tbreport.splitTime();
        },

        normalTime: function () {
            $(".time").each(function (index, element) {
                var time = $(this).text().trim();
                if(time.indexOf("分") > 0){
                    time = time.substring(0, time.indexOf("分"));
                    $(this).text($.tbreport.getMinite(time) + "分");
                }else{
                    $(this).text($.tbreport.getMinite(time));
                }
            });
        },

        splitTime: function () {
            $(".timeSplit").each(function (index, element) {
                var str = $(this).text();
                var result = str.split("/");
                if (result.length >= 2) {
                    var timeStr = result[result.length - 1].trim();
                    timeStr = timeStr.substring(0, timeStr.indexOf("分"));
                    timeStr = " " + $.tbreport.getMinite(timeStr) + "分";
                    result[result.length - 1] = timeStr;
                    $(this).text(result.join("/"));
                }

            });
        },

        getMinite: function (time) {
            if (time != "") {
                time = parseInt(time);
                time = time / 60;
                return time.toFixed(2);
            }
            return "";
        },

        getTimeStr: function (time) {
            if (time == "") {
                return "-";
            }
            var newDate = new Date();
            newDate.setTime(time);
            return newDate.format('yyyy-MM-dd');
        },
        //反欺诈
        antiFraud: function () {
            var partnerCount = $("#partnerCount");
            var tr1 = $(partnerCount).find("td").eq(2).text();
            if (tr1 != "") {
                tr1 = tr1.substring(0, tr1.indexOf("个"));
                if (tr1 >= 5) {
                    $(partnerCount).attr("class", "bc-warn");
                }
            }

            var idcCount = $("#idcCount");
            var tr2 = $(idcCount).find("td").eq(2).text();
            if (tr2 != "") {
                tr2 = tr2.substring(0, tr2.indexOf("个"));
                if (tr2 >= 3) {
                    $(idcCount).attr("class", "bc-warn");
                }
            }

            var phoneCount = $("#phoneCount");
            var tr3 = $(phoneCount).find("td").eq(2).text();
            if (tr3 != "") {
                tr3 = tr3.substring(0, tr3.indexOf("个"));
                if (tr3 >= 5) {
                    $(phoneCount).attr("class", "bc-warn");
                }

                var starnetCount = $("#starnetCount");
                var tr4 = $(starnetCount).find("td").eq(2).text();
                if (tr4 != "") {
                    tr4 = tr4.substring(0, tr4.indexOf("个"));
                    if (tr4 >= 6) {
                        $(starnetCount).attr("class", "bc-warn");
                    }
                }

            }
        }


    }
})(jQuery);



