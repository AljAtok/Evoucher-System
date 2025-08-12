$(document).ready(function () {
    const base_url  = $("#base_url").val();

    $(document).on('submit', '#otp-form', function(e){
    	e.preventDefault();
    	$('#otp-msg').empty();

    	var otp_code = $('#otp-code').val();
    	$.ajax({
            url: base_url + 'login/authenticate-otp-code/',
            data: {otp_code:otp_code},
            method: 'POST',
            success:function(response){
                let parse_response = JSON.parse(response);
                if(parse_response['result'] == 1){
                	var msg = parse_response['msg'];
                	var link = parse_response['link'];

                	$('#otp-msg').removeClass('text-danger');
                	$('#otp-msg').addClass('text-success');

                	$('#otp-msg').empty();
                	$('#otp-msg').append(msg + '<br /><br />');

                	window.setTimeout( function(){
                         window.location = link;
                    }, 2500);

                }else if(parse_response['result'] == 2){
                	var msg = parse_response['msg'];
                	var link = parse_response['link'];

                	$('#otp-msg').removeClass('text-success');
                	$('#otp-msg').addClass('text-danger');

                	$('#otp-msg').empty();
                	$('#otp-msg').append(msg + '<br /><br />');

                	$('#otp-form-inputs').empty();

                	window.setTimeout( function(){
                         window.location = link;
                    }, 2500);
                }else{
                    var msg = parse_response['msg'];

                	$('#otp-msg').removeClass('text-success');
                	$('#otp-msg').addClass('text-danger');

                	$('#otp-msg').empty();
                	$('#otp-msg').append(msg + '<br /><br />');
                }
            }
        });
    });
});