@extends('_layouts.admin')

@section('content')
<button type="button" class="btn btn-lg btn-primary" style="margin:10px 10px" data-toggle="modal" data-target="#saveModal" data-remark="add">新增</button>
<table id="example" class="display" class="table table-striped table-bordered" cellspacing="0" width="100%"></table>

<!-- dateTables配置 -->
<script>
    $(document).ready(function() {
        $.get('/app/all-apps', function(rs){
            $('#example').DataTable({
                "data": rs.data,
                "columns": [
                    { "title": "Action", "orderable": false, "defaultContent": "" },
                    { "title": "", "class": "datatable-logo" },
                    { "title": "Name", "class": "datatable-name" },
                    { "title": "ClassName", "class": "datatable-classname" },
                    { "title": "Download", "class": "datatable-download" },
                    { "title": "Status", "class": "datatable-status" },
                    { "title": "Android", "class": "datatable-android" },
                    { "title": "Ios", "class": "datatable-ios" },
                    { "title": "Slogan", "class": "datatable-slogan" },
                    { "title": "Introduce", "class": "datatable-introduce" }
                ],
                "iDisplayLength":30
            });
        },'json')

    } );
</script>
<!-- 添加/编辑弹框 -->
<div class="modal fade" id="saveModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">添加</h4>
            </div>
            <div class="modal-body">
                <form action="#" id="saveAppForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="saveInputName">Name</label>
                        <input type="text" class="form-control" id="saveInputName" name="name" placeholder="她的名字......">
                    </div>
                    <div class="form-group">
                        <label for="saveInputAndroid">Android</label>
                        <input type="text" class="form-control" id="saveInputAndroid" name="android" placeholder="她在安卓村的住址......">
                    </div>
                    <div class="form-group">
                        <label for="saveInputIos">Ios</label>
                        <input type="text" class="form-control" id="saveInputIos" name="ios" placeholder="她在苹果村的住址......">
                    </div>
                    <div class="form-group">
                        <label for="saveInputDownload">Download</label>
                        <input type="text" class="form-control" id="saveInputDownload" name="download" placeholder="嗯......她总共被约会了几次......">
                    </div>
                    <div class="form-group">
                        <label for="saveInputSlogan">Slogan</label>
                        <input type="text" class="form-control" id="saveInputSlogan" name="slogan" placeholder="给她贴个标签......">
                    </div>
                    <div class="form-group">
                        <label for="saveInputIntroduce">Introduce</label>
                        <textarea class="form-control" id="saveInputIntroduce" name="introduce" placeholder="介绍一下她吧......" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label >ClassName</label>
                        <select class="form-control" name="class_name" id="saveSelectClassName">
                            @foreach ($categories as $cat)
                                <option>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="logoShow">
                    </div>
                    <div class="form-group">
                        <label for="saveInputLogo">Logo</label>
                        <input type="file" id="saveInputLogo" name="logo">
                        <p class="help-block">最后来个定妆照呗......</p>
                    </div>
                    <div id="hidden_data"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" onclick="$('#saveAppForm').submit();" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#saveModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            if(button.attr('data-remark') == 'edit') {
                //编辑
                var app = $(event.relatedTarget).parents('tr');
                $('#saveInputName').val(app.find('td.datatable-name').text());
                $('#saveInputAndroid').val(app.find('td.datatable-android').text());
                $('#saveInputIos').val(app.find('td.datatable-ios').text());
                $('#saveInputDownload').val(app.find('td.datatable-download').text());
                $('#saveInputSlogan').val(app.find('td.datatable-slogan span').text());
                $('#saveInputIntroduce').val(app.find('td.datatable-introduce span').text());
                //class_name
                var class_name = app.find('td.datatable-classname').text();
                $('#saveSelectClassName').find('option:contains('+class_name+')').attr('selected', 'selected');
                //Logo
                $('#logoShow').html(app.find('td.datatable-logo').html());
                //添加->编辑
                $('#myModalLabel').text('编辑');
                $('#hidden_data').html('<input type="hidden" name="_method" value="PUT">');
                $('#saveAppForm').attr('action', '/app/'+app.find('td:first input').val());
            } else {
                //新增
                $('#saveInputName').val("");
                $('#saveInputAndroid').val("");
                $('#saveInputIos').val("");
                $('#saveInputDownload').val("");
                $('#saveInputSlogan').val("");
                $('#saveInputIntroduce').val("");
                $('#logoShow').html("");
                $('#myModalLabel').text('添加');
                $('#hidden_data').html('<input type="hidden" name="_method" value="POST">');
                $('#saveAppForm').attr('action', '/app');
            }
            $('#hidden_data').append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
            //清空上传图片
            $('#saveInputLogo')[0].outerHTML = $('#saveInputLogo')[0].outerHTML;
        });
        //删除
        $('#delModal').on('show.bs.modal', function (event) {
            var app = $(event.relatedTarget).parents('tr');
            var id = app.find('td:first input').val();
            $('#del_app_id').val(id);
            $('#deleteAppForm').attr('action', '/app/'+id);
        })
    })
</script>
<!-- 删除提示框 -->
<div class="modal fade" id="delModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="#" id="deleteAppForm" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">删除</h4>
                </div>
                <div class="modal-body">
                    三思啊......女王大人......
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="del_app_id" name="id" value="">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="delApp" onclick="$('#deleteAppForm').submit()">Del</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection