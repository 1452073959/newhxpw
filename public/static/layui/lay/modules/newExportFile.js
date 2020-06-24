parseTempData = function(item3, content, tplData, text){
    var str = item3.templet ? function(){
      return typeof item3.templet === 'function' 
        ? item3.templet(tplData)
      : laytpl($(item3.templet).html() || String(content)).render(tplData) 
    }() : content;
    return text ? $('<div>'+ str +'</div>').text() : str;
};
/**
  * @exportFile  重构layui table.exportFile 下载时不能修改excel表名问题
  * @author  Han
  * @version  1.0
  * @param id   [Array]   表头
  * @param data [Array]   二维数组 
  * @param type [String]  下载类型
  * @param name [String]  自定义表名
*/
table.exportFile = function(id, data, type, name){
    data = data || table.clearCacheKey(table.cache[id]);
    type = type || 'csv';
    
    var config = table.config[id] || {}
    ,textType = ({
      csv: 'text/csv'
      ,xls: 'application/vnd.ms-excel'
    })[type]
    ,alink = document.createElement("a");
     var Sys = {};
     var device = navigator.userAgent.toLowerCase();
     var s;
     (s = device.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] :
     (s = device.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] :
     (s = device.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] :
     (s = device.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] :
     (s = device.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0;
    if(Sys.ie) return hint.error('IE_NOT_SUPPORT_EXPORTS');
    alink.href = 'data:'+ textType +';charset=utf-8,\ufeff'+ encodeURIComponent(function(){
      var dataTitle = [], dataMain = [];
      layui.each(data, function(i1, item1){
        var vals = [];
        if(typeof id === 'object'){ //ID直接为表头数据
          layui.each(id, function(i, item){
            i1 == 0 && dataTitle.push(item || '');
          });
          layui.each(table.clearCacheKey(item1), function(i2, item2){
             vals.push('"'+ (item2 || '') +'"');
          });
        } else {
          table.eachCols(id, function(i3, item3){
            if(item3.field && item3.type == 'normal' && !item3.hide){
              i1 == 0 && dataTitle.push(item3.title || '');
              vals.push('"'+ parseTempData(item3, item1[item3.field], item1, 'text') + '"');
            }
          });
        }
        dataMain.push(vals.join(','))
      });
      
      return dataTitle.join(',') + '\r\n' + dataMain.join('\r\n');
    }());
    
    alink.download = (name || config.title || 'table_'+ (config.index || '')) + '.' + type; 
    document.body.appendChild(alink);
    alink.click();
    document.body.removeChild(alink); 
};