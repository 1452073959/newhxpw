    layui.use('element', function(){
      var element = layui.element;
    });
    layui.use('form', function(){
        var form = layui.form;
        // form.render();
        form.on('radio(design_type)', function(data){
            if(data.value == 1){
                $('#design_table1').show();
                $('#design_table2').hide();
            }else{
                $('#design_table1').hide();
                $('#design_table2').show();
            }
        }); 
    });
    set_t_sort();
    //弹框 (新的)
    $(document).on('click','#showlayer',function(){
        var bh=$(window).height();//获取屏幕高度
        var bw=$(window).width();//获取屏幕宽度
        $("#layer").css({
            height:bh,
            width:bw,
            display:"block"
        });
        if(bh < 700){
            $(".pop").css({
                height:'90%',
                top:'5%',
                width:'80%',
                left:'10%',
            });
        }
        $('.diolog_new').css({height:($(".pop").height()-140)+'px'})
        $('#o_remark').css({height:($(".pop").height()-170)+'px'})
        $(".diolog_new1").show();
    })
    $(document).on('click','#set_gift',function(){
        var bh=$(window).height();//获取屏幕高度
        var bw=$(window).width();//获取屏幕宽度
        $("#layer").css({
            height:bh,
            width:bw,
            display:"block"
        });
        if(bh < 700){
            $(".pop").css({
                height:'90%',
                top:'5%',
                width:'80%',
                left:'10%',
            });
        }
        $('.diolog_new').css({height:($(".pop").height()-140)+'px'})
        $('#o_remark').css({height:($(".pop").height()-170)+'px'})
        $(".diolog_new2").show();
    })
    $(document).on('click','#set_o_remark',function(){
        var bh=$(window).height();//获取屏幕高度
        var bw=$(window).width();//获取屏幕宽度
        $("#layer").css({
            height:bh,
            width:bw,
            display:"block"
        });
        if(bh < 700){
            $(".pop").css({
                height:'90%',
                top:'5%',
                width:'80%',
                left:'10%',
            });
        }
        $('.diolog_new').css({height:($(".pop").height()-140)+'px'})
        $('#o_remark').css({height:($(".pop").height()-170)+'px'})
        $(".diolog_new3").show();
    })
    $('.close_new_diolog').click(function(){
         $("#layer,.diolog_new1,.diolog_new2,.diolog_new3").hide();
    })
    

    //=====取费模板相关
    function add_tr(o){
        var html = '';
        html += '     <tr>'
        html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
        html += '       <td>'
        html += '          <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
        html += '          <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
        html += '          <a href="javascript:;" onclick="delete_tr(this)"><i class="layui-icon layui-icon-delete "></i></a>'
        html += '       </td>'
        html += '       <td><input class="layui-input" name="t_name[]" /></td>'
        html += '       <td><input class="layui-input" name="t_sign[]" /></td>'
        html += '       <td><input class="layui-input" name="t_formula[]" /></td>'
        html += '       <td><input class="layui-input" name="t_rate[]" /></td>'
        html += '       <td class="price"></td>'
        html += '       <td><input class="layui-input" name="t_content[]" /></td>'
        html += '     </tr>'
        $(o).parent().parent().after(html);
        set_t_sort();
    }
    function delete_tr(o){
        $(o).parent().parent().remove();
        set_t_sort();
    }
    //重新排序
    function set_t_sort(){
        var i = 1;
        $('.t_sort').each(function(k,v){
            $(v).attr('value',i);
            $(v).attr('bj',i);
            i++;
        })
    }
    //插入排序
    $(document).on('change','.t_sort',function(){
        var num = $(this).val();
        var old_num = $(this).attr('bj');//目标位置
        var data_tr =  $(this).parent().parent();
        var i = 0;
        var is = false;
        $('.t_sort').each(function(k,v){
            if($(v).attr('bj') == num){
                if(num > old_num){
                    $(data_tr).insertAfter($(v).parent().parent()); //将本身插入到目标tr的前面 
                }else{
                    $(data_tr).insertBefore($(v).parent().parent()); //将本身插入到目标tr的前面 
                }
                is = true;
            }
        })
        if(!is){
            $(data_tr).insertAfter($('.t_sort:last').parent().parent());
        }
        set_t_sort();
    })
    $(document).on('click','.t_asc',function(){
        var this_tr = $(this).parent().parent().parent();
        if($(this_tr).prev().attr('class') != 'stop'){
            var num = $(this_tr).find('td').eq(0).find('input').attr('bj');
            $('.t_sort').each(function(k,v){
                if((Number($(v).attr('bj'))+1) == num){
                   $(this_tr).insertBefore($(v).parent().parent()); //将本身插入到目标tr的前面 
                }
            })
        }
        
        set_t_sort();
    })
    $(document).on('click','.t_desc',function(){
        var this_tr = $(this).parent().parent().parent();
        if($(this_tr).next().attr('class') != 'stop'){
            var num = $(this_tr).find('td').eq(0).find('input').attr('bj');
            $('.t_sort').each(function(k,v){
                if((Number($(v).attr('bj'))-1) == num){
                    console.log((Number($(v).attr('bj'))-1));
                   $(this_tr).insertAfter($(v).parent().parent()); //将本身插入到目标tr的前面 
                }
            })
        }
        
        set_t_sort();
    })
    //=====取费模板相关

    //输入选择框
    // $(document).on('change','.input_select',function(){
    //     var str = $(this).val();
    //     var unit = $(this).find("option:selected").attr("data-unit")
    //     var price = $(this).find("option:selected").attr('data-price');
    //     var material = $(this).find("option:selected").attr('data-material');
    //     $('input[name="discount_val"]').val(str);
    //     $(this).parent().parent().parent().parent().parent().find('td').eq(1).find('input').val(unit);
    //     $(this).parent().parent().parent().parent().parent().find('td').eq(3).find('input').val(price);
    //     $(this).parent().parent().parent().parent().parent().find('td').eq(4).find('input').val(material);
    // })
    $(document).on('change','#design_select',function(){
        var unit = $(this).find("option:selected").attr("data-unit")
        var price = $(this).find("option:selected").attr('data-price');
        var material = $(this).find("option:selected").attr('data-material');
        $(this).parent().parent().find('td').eq(1).text(unit);
        $(this).parent().parent().find('td').eq(3).text(price);
        $(this).parent().parent().find('td').eq(4).text(material);
    })
    $(document).on('click','.gift_parent',function(){
        var cate = $(this).data('cate');
        if($('.gift_child[data-cate="'+cate+'"]').eq(0).css('display') == 'none'){
            $('.gift_child[data-cate="'+cate+'"]').show();
        }else{
            $('.gift_child[data-cate="'+cate+'"]').hide();
        }
    })
    $(document).on('click','.gift_child',function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        var brand = $(this).data('brand');
        var price = $(this).data('price');
        var content = $(this).data('content');
        var unit = $(this).data('unit');
        var html = '';

        if($('.gid_'+id).length > 0){
            layui.use('layer', function(){
                var layer = layui.layer;
                layer.msg('礼品已添加');
            })
            return false;
        }
        html += '<tr class="gid_'+id+'">'
        html += '    <td class="count">'+ ($('.gift_table').find('tbody').find('tr').length+1) +'</td>'
        html += '    <td>'
        html += '        <i class="layui-icon layui-icon-delete"></i>'
        html += '    </td>'
        html += '    <td>'+name+'</td>'
        html += '    <td>'+brand+'</td>'
        html += '    <td><input name="gift['+id+']" value="1" class="gift_input"/></td>'
        html += '    <td>'+unit+'</td>'
        html += '    <td class="gift_price">'+price+'</td>'
        html += '    <td class="gift_total_one">'+price+'</td>'
        html += '    <td>'+content+'</td>'
        html += '</tr>'
        $('.gift_table').find('tbody').append(html);
        giftSortPrice();
    })
    function giftSortPrice(){
        $('.gift_table').find('tr').each(function(k,v){
            $(v).find('.count').text(k);
        })
        var total = 0;
        $('.gift_total_one').each(function(k,v){
            total += Number($(v).text());
        })
        $('#gift_total_price').text(total.toFixed(2));
    }
    $(document).on('click','.gift_table .layui-icon-delete',function(){
        $(this).parent().parent().remove();
        giftSortPrice();
    })
    //选择取费模板
    $(document).on('change','#check_tmp_diolog',function(){
        var tmp_id = $(this).val();
        $.post('/admin/quote/get_tmp_cost_info',{tmp_id:tmp_id},function(res){
            var html  = ''
            html += '     <tr class="dnone">'
            html += '       <td class="hide_td"></td>'
            html += '       <td>'
            html += '       </td>'
            html += '       <td>材料直接费</td>'
            html += '       <td>A3</td>'
            html += '       <td>A3</td>'
            html += '       <td>100</td>'
            html += '       <td></td>'
            html += '       <td></td>'
            html += '     </tr>'
            // 要个初始值  不然第一个为空 后面就对不上了
            html += '     <tr class="dnone" style="display:none">'
            html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
            html += '       <td>'
            html += '          <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
            html += '          <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
            html += '          <a href="javascript:;" onclick="delete_tr(this)"><i class="layui-icon layui-icon-delete "></i></a>'
            html += '       </td>'
            html += '       <td><input class="layui-input" value="1" name="t_name[]" /></td>'
            html += '       <td><input class="layui-input" value="1" name="t_sign[]" /></td>'
            html += '       <td><input class="layui-input" value="1" name="t_formula[]" /></td>'
            html += '       <td><input class="layui-input" value="1" name="t_rate[]" /></td>'
            html += '       <td></td>'
            html += '       <td><input class="layui-input" value="1" name="t_content[]" /></td>'
            html += '     </tr>'
            // 要个初始值  不然第一个为空 后面就对不上了 end
            if(res.code == 1 && res.datas.length > 0){
                $(res.datas).each(function(k,v){
                     //自己添加的
                    if(v.name == '工程报价' || v.name== '直接费' || v.name== '工程报价'){
                        html += '     <tr>'
                        html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                        html += '       <td>'
                        html += '          <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                        html += '          <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                        html += '       </td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.name+'" name="t_name[]" />'+v.name+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.sign+'" name="t_sign[]" />'+v.sign+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.formula+'" name="t_formula[]" />'+v.formula+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.rate+'" name="t_rate[]" />'+v.rate+'</td>'
                        html += '       <td class="price">0</td>'
                        html += '       <td><input class="layui-input" placeholder="(选填)" value="'+v.content+'" name="t_content[]" /></td>'
                        html += '     </tr>'
                    }else if(v.name== '优惠'){
                        html += '     <tr>'
                        html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                        html += '       <td>'
                        html += '          <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                        html += '          <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                        html += '       </td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.name+'" name="t_name[]" />'+v.name+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.sign+'" name="t_sign[]" />'+v.sign+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.formula+'" name="t_formula[]" />'+v.formula+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.rate+'" name="t_rate[]" />'+v.rate+'</td>'
                        html += '       <td class="price">0</td>'
                        html += '       <td><input class="layui-input" type="hidden" placeholder="(选填)" value="'+v.content+'" name="t_content[]" /></td>'
                        html += '     </tr>'
                    }else if(v.name== '总计'){
                        html += '     <tr class="stop">'
                        html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                        html += '       <td>'
                        html += '       </td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.name+'" name="t_name[]" />'+v.name+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.sign+'" name="t_sign[]" />'+v.sign+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.formula+'" name="t_formula[]" />'+v.formula+'</td>'
                        html += '       <td><input type="hidden" class="layui-input" value="'+v.rate+'" name="t_rate[]" />'+v.rate+'</td>'
                        html += '       <td class="price">0</td>'
                        html += '       <td><input class="layui-input" placeholder="(选填)" value="'+v.content+'" name="t_content[]" /></td>'
                        html += '     </tr>'
                    }else if(v.name== '优惠前报价'){
                        
                    }else{
                        html += '     <tr>'
                        html += '       <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                        html += '       <td>'
                        html += '          <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                        html += '          <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                        html += '          <a href="javascript:;" onclick="delete_tr(this)"><i class="layui-icon layui-icon-delete "></i></a>'
                        html += '       </td>'
                        html += '       <td><input class="layui-input" value="'+v.name+'" name="t_name[]" /></td>'
                        html += '       <td><input class="layui-input" value="'+v.sign+'" name="t_sign[]" /></td>'
                        html += '       <td><input class="layui-input" value="'+v.formula+'" name="t_formula[]" /></td>'
                        html += '       <td><input class="layui-input" value="'+v.rate+'" name="t_rate[]" /></td>'
                        html += '       <td class="price">0</td>'
                        html += '       <td><input class="layui-input" value="'+v.content+'" name="t_content[]" /></td>'
                        html += '     </tr>'
                    }
                })
            }else{
                html+= '<tr>'
                html+= '    <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                html+= '    <td>'
                html+= '        <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                html+= '        <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                html+= '    </td>'
                html+= '    <td><input type="hidden" class="layui-input" value="直接费" name="t_name[]" />直接费</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="A1" name="t_sign[]" />A1</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="A1" name="t_formula[]" />A1</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="100" name="t_rate[]" />100</td>'
                html+= '    <td class="price"></td>'
                html+= '    <td><input class="layui-input" placeholder="(选填)" value="" name="t_content[]" /></td>'
                html+= '</tr>'
                html+= '<tr>'
                html+= '    <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                html+= '    <td>'
                html+= '        <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                html+= '        <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                html+= '    </td>'
                html+= '    <td><input type="hidden" class="layui-input" value="优惠" name="t_name[]" />优惠</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="A2" name="t_sign[]" />A2</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="A2" name="t_formula[]" />A2</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="100" name="t_rate[]" />100</td>'
                html+= '    <td class="price"></td>'
                html+= '    <td><input class="layui-input" type="hidden" placeholder="(选填)" value="" name="t_content[]" /></td>'
                html+= '</tr>'
                html+= '<tr>'
                html+= '    <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                html+= '    <td>'
                html+= '        <span class="layui-table-sort layui-inline"><i class="layui-edge layui-table-sort-asc t_asc" title="向上移动"></i><i class="layui-edge layui-table-sort-desc t_desc" title="向下移动"></i></span>'
                html+= '        <a href="javascript:;" onclick="add_tr(this)"><i class="layui-icon layui-icon-add-1"></i></a>'
                html+= '    </td>'
                html+= '    <td><input type="hidden" class="layui-input" value="工程报价" name="t_name[]" />工程报价</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="S" name="t_sign[]" />S</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="S" name="t_formula[]" />S</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="100" name="t_rate[]" />100</td>'
                html+= '    <td class="price"></td>'
                html+= '    <td><input class="layui-input" placeholder="(选填)" value="" name="t_content[]" /></td>'
                html+= '</tr>'
                html+= '<tr class="stop">'
                html+= '    <td class="hide_td"><input class="layui-input t_sort" name="t_sort[]" /></td>'
                html+= '    <td></td>'
                html+= '    <td><input type="hidden" class="layui-input" value="总计" name="t_name[]" />总计</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="T" name="t_sign[]" />T</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="T" name="t_formula[]" />T</td>'
                html+= '    <td><input type="hidden" class="layui-input" value="100" name="t_rate[]" />100</td>'
                html+= '    <td class="price"></td>'
                html+= '    <td><input class="layui-input" placeholder="(选填)" value="" name="t_content[]" /></td>'
                html+= '</tr>'
            }
            $('#tmp_table tbody').html();
            $('#tmp_table tbody').html(html);
            set_t_sort();
        },'json')
    })

    //费率预览 / 保存
    $('#preview_diolog_new1').click(function(){

        var is_null = 0;
        var customerid = $('input[name="customerid"]').val();
        if($('#project_datas input').length < 1){
            layui.use('layer', function(){
                layer.msg('未选择报价条目');
            }); 
            return false;
        }
        $('#project_datas input').not('.sort , .unit , .content').each(function(k,v){
            if($(v).val() == ''){
                var word = $(v).parent().parent().attr('data-inword');
                var project = $(v).attr('data-project');
                layui.use('layer', function(){
                    layer.msg(word+'-'+project+' 数量不得为空');
                }); 
                is_null = 1;
                return null;
            }
        })
        if(is_null){
            return false;
        }
        var datas = $('#project .project input ,#diolog_new1_form').serializeArray();
        project_datas['customerid'] = customerid;
        console.log(project);
        $.post('/admin/offerlist/save_order_cost_cache',datas,function(res){
            if(res.code == 1){
                $.each(res.data.order_cost, function (k, v) {
                    $('#tmp_table tbody').find('tr').eq(Number(k)+2).find('.price').text(v.price);
                })
                $('#t_order_cost_all_price').text(res.data.order_cost_all_price);
                $('#design_price').text(res.data.design_price);
                $('#discount_price').text(res.data.discount);
                //外面页面的工程报价和费率费用
                $('#g3').find('i').text(res.data.discount_proquant);
                $('#g2').find('i').text(res.data.order_cost_all_price);
            }else{
                //失败 提醒
                layui.use('layer', function(){
                    layer.msg(res.msg);
                }); 
            }
        })
    })
    var old_g_num = '';
    $(document).on('focus',".gift_input", function () {
        old_g_num = $(this).val();
    })
    
    $(document).on('change',".gift_input",function(){
        var new_g_num = $(this).val();
        var ex = /^\d+$/;
        if(!ex.test(new_g_num) || new_g_num == 0){
            layui.use('layer', function(){
                layer.msg('数量必须为正整数');
            }); 
            $(this).val(old_g_num);
            return false;
        }
        var price = $(this).parent().parent().find('.gift_price').text();
        $(this).parent().parent().find('.gift_total_one').text((Number(new_g_num)*Number(price)).toFixed(2));
        var total = 0;
        $('.gift_total_one').each(function(k,v){
            total += Number($(v).text());
        })
        $('#gift_total_price').text(total.toFixed(2));
    })

    $(document).on('click','.add_discount .layui-icon-add-1',function(){
        var html = $(this).parent().parent().parent().prop("outerHTML");
        html = html.replace('<i class="layui-icon layui-icon-add-1">','<i class="layui-icon layui-icon-delete"></i>')
        $('.discount_div:last').after(html);
    })
    $(document).on('click','.add_discount .layui-icon-delete',function(){
        $(this).parent().parent().parent().remove();
    })
    $(document).on('change','.discount_div select',function(){
        var content = $(this).find('option:selected').attr('data-content');
        $(this).parent().find('input[name="discount_content[]"]').val(content);
        $(this).parent().find('input[name="discount_rate[]"]').val(0);
    })


