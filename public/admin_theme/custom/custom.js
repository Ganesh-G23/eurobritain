function processAjaxResponse(res, time = 1500, el = undefined,scrollTop="yes"){
	var status = false;
	if(res['status'] == 1){
		if(el){
			$(el).find('.ajax-msg').html('<div class="alert alert-success" role="alert"><div class="alert-heading">'+res['msg']+'</div></div>');
		}else{
			$('.ajax-msg').html('<div class="alert alert-success" role="alert"><div class="alert-heading">'+res['msg']+'</div></div>');
		}
		if(res['redirect_url']){
			setTimeout(function(){
				window.location.href = res['redirect_url'];
			}, time);
		}
		status = true;
	}else if(res['status'] == 0){
		if(res['error'] && res['error'] != ''){
			if(el){
				$(el).find('.ajax-msg').html('<div class="alert alert-danger" role="alert"><span>'+res['error']+'</span></div>');
			} else{
				$('.ajax-msg').html('<div class="alert alert-danger" role="alert"><span>'+res['error']+'</span></div>');
			}
		}else if(res['error_array']){
			Object.keys(res['error_array']).map(function(key){
				$('[name="'+key+'"]').closest('.ajax-field').find('.ajax-error').html(res['error_array'][key]);
			});
		}
	}
	if(scrollTop=="yes"){
		if(el){
		    console.log($(el).offset());
			if(res['status'] == 0){
				if(res['error_array']){
					if(Object.keys(res['error_array'])[0]){
						$('html,body').animate({scrollTop: $(el).find('[name="'+Object.keys(res['error_array'])[0]+'"]').offset().top-75},'slow');
					}else{
						$('html,body').animate({scrollTop: $(el).offset().top-75},'slow');
					}
				}else{
					$('html,body').animate({scrollTop: $(el).offset().top-75},'slow');
				}
			}else{
				$('html,body').animate({scrollTop: $(el).offset().top-75},'slow');
			}
		}else{
			$('html, body').animate({scrollTop: 0}, 500);
		}
    }
    return status;
}

function clearAjaxErrors(el){
	if(el){
		$(el).find('.ajax-error').html('');
		$(el).find('.ajax-msg').html('');
	}else{
		$('.ajax-error').html('');
		$('.ajax-msg').html('');
	}
}

function fillAjaxForm(data){
	Object.keys(data).map(function(key){
		$('#ajax-form').find('[name="'+key+'"]').val(data[key]);
	});
}

function show_loader(){
	$('#custom-loader').css('display', 'flex');
}

function hide_loader(){
	$('#custom-loader').css('display', 'none');
}