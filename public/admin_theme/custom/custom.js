function processAjaxResponse(res, time = 1500, el = undefined,scrollTop="yes"){
	var status = false;
	var firstMatchedField = null;
	var unmatchedErrors = [];
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
				var $field = $('[name="'+key+'"]');
				if(!$field.length){
					$field = $('[data-field="'+key+'"]');
				}
				var $errorTarget = $field.closest('.ajax-field').find('.ajax-error').first();
				if(!$errorTarget.length){
					$errorTarget = $('[data-field="'+key+'"].ajax-error');
				}
				if($errorTarget.length){
					$errorTarget.html(res['error_array'][key]);
					if(!firstMatchedField && $field.length){ firstMatchedField = $field; }
				}else{
					unmatchedErrors.push(res['error_array'][key]);
				}
			});
			if(unmatchedErrors.length){
				var html = '<div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3">';
				unmatchedErrors.forEach(function(msg){ html += '<li>'+msg+'</li>'; });
				html += '</ul></div>';
				if(el){ $(el).find('.ajax-msg').html(html); } else { $('.ajax-msg').html(html); }
			}
		}
	}
	if(scrollTop=="yes"){
		if(el){
			if(res['status'] == 0){
				var $scrollTarget = firstMatchedField && firstMatchedField.length ? firstMatchedField : $(el);
				var offset = $scrollTarget.offset();
				if(offset){
					$('html,body').animate({scrollTop: offset.top-75},'slow');
				}
			}else{
				var elOffset = $(el).offset();
				if(elOffset){
					$('html,body').animate({scrollTop: elOffset.top-75},'slow');
				}
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