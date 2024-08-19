{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/umspay" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">
                    <div class="panel-title">UMSPay Settings</div>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Api Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="apikey" name="apikey" placeholder="xxxxxxxxxxxxxxxxx" value="{$_c['umspay_api_key']}" required>
                            <small class="form-text text-muted">Login to <a href="https://portal.umeskiasoftwares.com/" target="_blank">https://portal.umeskiasoftwares.com</a> to get your api key on settings page</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Email</label>
                        <div class="col-md-6">
                            <input type="email" class="form-control" id="email" name="email" placeholder="example@gmail.com" value="{$_c['umspay_email']}" required>
                            <small class="form-text text-muted">Enter your email address that you used to register on <a href="https://portal.umeskiasoftwares.com/" target="_blank">https://portal.umeskiasoftwares.com</a></small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="col-md-2 control-label">Account ID <small class="text-info">(Optional)</small></label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="account_id" name="account_id" placeholder="xxxxxxxxxxxxxxxxx" value="{$_c['umspay_account_id']}">
                            <small class="form-text text-muted">Login to <a href="https://portal.umeskiasoftwares.com/servicepoint.php?ptd=umspay&account" target="_blank">https://portal.umeskiasoftwares.com</a> to get your account id on settings page</small>
                            <br>
                            <span class="text-info">This is optional, if your account has one copy and paste it here</span>
                        </div>
                    </div>
                    
                    
                     <div class="form-group">
                        <label class="col-md-2 control-label">Webhook</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="webhook"  value="">
                        </div>
                    </div>


                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">SAVE CHANGES</button>
                        </div>
                    </div>
                        <pre>/ip hotspot walled-garden
                   add dst-host=umeskiasoftwares.com
                   add dst-host=*.umeskiasoftwares.com</pre>
                </div>
            </div>

        </div>
    </div>
</form>

<script>
let input = document.getElementById('webhook');
var fullURL = window.location.href;
 input.value = "https://"+fullURL.split('/')[2]+"/index.php?_route=callback/umspay";
</script>
{include file="sections/footer.tpl"}
