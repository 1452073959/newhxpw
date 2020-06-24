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

    $.mnoReport.highlight();
    $.mnoReport.formateTimeStamp();
    $.mnoReport.transferSec2Min();
    $.mnoReport.initBtn();
    $.mnoReport.formatStatusTag();


});


(function ($) {

    $.mnoReport = $.fn.mnoReport = {
        highlight: function () {
            $.mnoReport.blackList();
            $.mnoReport.antiFraud();
            $.mnoReport.crossValidation();
            $.mnoReport.cuishou();
        },
        formatStatusTag:function(){
            var mobileStatus = $("#mobileStatus").text();
            if(mobileStatus == ""){
                $("#mobileStatus").attr("class","");
            }
            if(mobileStatus.indexOf("停") >= 0 || mobileStatus.indexOf("欠费") >= 0){
                $("#mobileStatus").attr("class","tag danger");
            }else{
                $("#mobileStatus").attr("class","tag normal");
            }
        },
        blackList: function () {
            $(".blackList").each(function (index, element) {
                var p = $(this);
                var span = p.find("span");
                var size = span.length;
                for (var i = 0; i < size / 2 - 1; i++) {
                    var span2 = span.eq(i*2 + 1).text().trim();
                    if (span2 == "高风险") {
                        span.eq(i*2 + 1).attr("class", "tag danger");
                        span.eq(i*2 + 2).attr("class", "msg danger");
                    }
                    if (span2 == "中风险") {
                        span.eq(i*2 + 1).attr("class", "tag warn");
                        span.eq(i*2 + 2).attr("class", "msg warn");
                    }
                }


            });
        },

        initBtn: function () {
            $("#down").click(function () {
                $("#emergencyDetail").slideToggle();
            });

            $("#up").click(function () {
                $("#emergencyDetail").slideToggle();
            });
        },
        cuishou: function () {
            var oneMonthDunningCount = $("#oneMonthDunningCount");
            var count = oneMonthDunningCount.text().trim();
            if (count >= 6) {
                $("#oneMonthDunningDay").attr("class", "font danger");
                oneMonthDunningCount.attr("class", "bcg danger");
            } else if(count >= 4){
                $("#oneMonthDunningDay").attr("class", "font warn");
                oneMonthDunningCount.attr("class", "bcg warn");
            }else{
                $("#oneMonthDunningDay").attr("class", "font normal");
                oneMonthDunningCount.attr("class", "bcg normal");
            }

            var _30To60DunningCount = $("#30To60DunningCount");
            count = _30To60DunningCount.text().trim();
            if (count >= 10) {
                $("#30To60DunningDay").attr("class", "font danger");
                _30To60DunningCount.attr("class", "bcg danger");
            } else if(count >= 8){
                $("#30To60DunningDay").attr("class", "font warn");
                _30To60DunningCount.attr("class", "bcg warn");
            }else {
                $("#30To60DunningDay").attr("class", "font normal");
                _30To60DunningCount.attr("class", "bcg normal");
            }
        },

        formateTimeStamp: function () {
            $(".timestamp").each(function (index, element) {
                var time = $(this).text();
                $(this).text($.mnoReport.getTimeStr(time));
            });
        },

        transferSec2Min: function () {
            $.mnoReport.normalTime();
            $.mnoReport.splitTime();
        },

        normalTime: function () {
            $(".time").each(function (index, element) {
                var time = $(this).text().trim();
                if(time.indexOf("分") > 0){
                    time = time.substring(0, time.indexOf("分"));
                    $(this).text($.mnoReport.getMinite(time) + "分");
                }else{
                    $(this).text($.mnoReport.getMinite(time));
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
                    timeStr = " " + $.mnoReport.getMinite(timeStr) + "分";
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

        crossValidation: function () {
            // class="font warn"
            //单月通话次数大于500次的月份
            var callSizeOver500Month = $("#callSizeOver500Month");
            var td = $(callSizeOver500Month).find("td").eq(1);
            var result = td.text().trim();
            if (result != "" && result != "无") {
                td.text("");
                var span = "<span class='font warn'>" + result + "</span>" + "<span class='tag warn left'>告警</span>";
                td.append(span);
            }

            //全天未使用短信和电话的天数大于150天的告警
            var notCallAndSmsDayCount = $("#notCallAndSmsDayCount");
            td = $(notCallAndSmsDayCount).find("td").eq(1);
            result = td.text().trim();
            if (result != "") {
                var count = result.substring(0, result.indexOf("天"));
                if (count >= 150) {
                    td.text("");
                    span = "<span class='font warn'>" + result + "</span>" + "<span class='tag warn left'>告警</span>";
                    td.append(span);
                }
            }

            //号码使用时长小于60天的告警
            var numberUsedLong = $("#numberUsedLong");
            td = $(numberUsedLong).find("td").eq(1);
            result = td.text().trim();
            if (result != "") {
                count = result.substring(2, result.indexOf("天"));
                if (count <= 60) {
                    td.text("");
                    span = "<span class='font warn'>" + result + "</span>" + "<span class='tag warn left'>告警</span>";
                    td.append(span);
                }
            }

            //夜间通话次数大于15次告警
            var nightCallCount = $("#nightCallCount");
            td = $(nightCallCount).find("td").eq(1);
            result = td.text().trim();
            if (result != "") {
                count = result.substring(0, result.indexOf("天"));
                if (count >= 15) {
                    td.text("");
                    span = "<span class='font warn'>" + result + "</span>" + "<span class='tag warn left'>告警</span>";
                    td.append(span);
                }
                td = $(nightCallCount).find("td").eq(2);
                result = td.text().trim();
                if(result.indexOf(";") > 0){
                    td.text("");
                    var _result = result.split(";");
                    if (_result.length == 2) {
                        var html = "<span>"+_result[0].trim()+"</span><br><span>"+_result[1].trim()+"</span>";
                        td.append(html);
                    }
                }
            }
        },

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
        },
        sortArray:function(array){
            var compare = function (x, y) {//比较函数
                if (x < y) {
                    return -1;
                } else if (x > y) {
                    return 1;
                } else {
                    return 0;
                }
            };
            return array.sort(compare);
        },
        searchArrayIndex:function(array,target){
            if (!Array.indexOf){
                Array.prototype.indexOf = function(el){
                    for (var i=0,n=this.length; i<n; i++){
                        if (this[i] === el){
                            return i;
                        }
                    }
                    return -1;
                }
            }
            return array.indexOf(target);
        }



    }
})(jQuery);



