(function ($, Drupal, drupalSettings) {
	var menuadded = "no";	
Drupal.behaviors.mongodb_api = {
  attach: function (context, settings) {
    
  $(document).ready(function () {	  
	//alert (drupalSettings.geoField);
	
		if (menuadded == "no" && drupalSettings.dataformlist !== undefined) {		
			var newmenu = '<ul class="menu">' + drupalSettings.dataformlist + '</ul>'
			//$("#block-mainnavigation ul > li.sf-depth-1:nth-child(3)").append(newmenu);
			$("#block-mainnavigation ul > li.sf-depth-1:last-child").append(newmenu);
			menuadded = "yes";
		}
	});
	
	/* $(".node-mongodb-information-form input, .node-mongodb-information-form select,.node-mongodb-information-edit-form input, .node-mongodb-information-edit-form select").each(function(){
		var stateAttr = $(this).parents(".form-wrapper").attr("data-drupal-states");
		
		if(typeof stateAttr !== 'undefined' && JSON.stringify(stateAttr).indexOf("required") != -1){
			$(this).prev("label").addClass("form-required");
		}
	});
	
	$("#edit-field-upload-key-0-upload").prev("label").addClass("form-required"); */
	
	// pre select the datauser selectbox values
	if($("input[name='datauser_hidden']").length && $("input[name='datauser_hidden']").val() != ''){
		var dataUser = $("input[name='datauser_hidden']").val().split(",");
		$("#edit-datauser option").each(function(){
			if(jQuery.inArray($(this).attr("value"), dataUser) !== -1){
				$(this).attr("selected","selected");
			}else{
				$(this).removeAttr("selected");
			}
			
		});
	}else{
		$("#edit-datauser option").each(function(){
			$(this).removeAttr("selected");
		});
	}
	
	$(".formfieldformat").change(function() {
		$(this).siblings('.unique_check').find("input").prop('checked',false);
		$(this).siblings('.multiple_check').find("input").prop('checked',false);
		
		$(this).siblings(".multiple_check").css("display","none");
		$(this).siblings(".unique_check").css("display","none");
		$(this).siblings(".dropdown_values").css("display","none");
		$(this).siblings(".formvalidation").css("display","none");
		if($(this).val() == "textfield"){
			$(this).siblings(".formvalidation").css("display","block");
			$(this).siblings(".multiple_check").css("display","block");
			$(this).siblings(".unique_check").css("display","block");
		}else if($(this).val() == "select"){
			$(this).siblings(".multiple_check").css("display","block");
			$(this).siblings(".dropdown_values").css("display","block");
		}else if($(this).val() == "radios"){
			$(this).siblings(".dropdown_values").css("display","block");
		}else if($(this).val() == "webform_image_file" || $(this).val() == "generic_element"){
			$(this).siblings(".multiple_check").css("display","block");
		}
	});
	$(".dropdown_values").css("display","none");
	$(".multiple_check").css("display","none");
	$(".formvalidation").css("display","none");
	$(".unique_check").css("display","none");
	$(".formfieldformat").each( function() {
		if($(this).val() == "textfield"){
			$(this).siblings(".formvalidation").css("display","block");
			$(this).siblings(".multiple_check").css("display","block");
			$(this).siblings(".unique_check").css("display","block");
		}else if($(this).val() == "select"){
			$(this).siblings(".multiple_check").css("display","block");
			$(this).siblings(".dropdown_values").css("display","block");
		}else if($(this).val() == "radios"){
			$(this).siblings(".dropdown_values").css("display","block");
		}else if($(this).val() == "webform_image_file" || $(this).val() == "generic_element"){
			$(this).siblings(".multiple_check").css("display","block");
		}
	});

	$(".multiple_attr_field").click(function(){
		if($(this).is(":checked")){
			$(this).parents('.multiple_check').next(".unique_check").find("input").attr('disabled',true);
			$(this).parents('.multiple_check').next(".unique_check").find("input").prop('checked',false);
		}else{
			$(this).parents('.multiple_check').next(".unique_check").find("input").attr('disabled',false);
		}
	});
	
	$(".multiple_attr_field").each(function(){
		if($(this).is(":checked")){
			$(this).parents('.multiple_check').next(".unique_check").find("input").attr('disabled',true);
			$(this).parents('.multiple_check').next(".unique_check").find("input").prop('checked',false);
		}else{
			$(this).parents('.multiple_check').next(".unique_check").find("input").attr('disabled',false);
		}
	});
	
	// disable/enable multiple checkbox for collection reation field
	$(".relative_field_format").change(function() {
		if ($(this).val() == "radio"){
			$(this).next(".multiple_check").find("input").attr('disabled',true);
			$(this).next(".multiple_check").find("input").prop('checked',false);
		}else{
			$(this).next(".multiple_check").find("input").attr('disabled',false);
		}
	});
	
	$(".relative_field_format").each( function() {	
		if ($(this).val() == "radio"){
			$(this).next(".multiple_check").find("input").attr('disabled',true);
			$(this).next(".multiple_check").find("input").prop('checked',false);
		}else{
			$(this).next(".multiple_check").find("input").attr('disabled',false);
		}
	});
  }	  
};
})(jQuery, Drupal, drupalSettings);

(function ($, Drupal) {
	$(document).ready(function () {
		$("#apiheader").click(function () {
			$header = $(this);    
			$content = $header.next();

			$content.slideToggle('medium', function () {
				$header.text(function () {
					return $content.is(":visible") ? "  Collapse JSON" : "  Expand JSON";
				});
			});

		});
		$("#copyToClipboard").click(function () { 
			var $temp = $("<input>");
			$("body").append($temp);  
			$temp.val($('.testcontent .json_container').text()).select();
			document.execCommand("copy");
			$temp.remove();
			$(this).html("Copied");
			setTimeout(function(){
				$("#copyToClipboard").html("Copy JSON");
			},500);
		});
		
		$('#dataform_list, .mongo-data-table .views-table').DataTable({
			//"scrollX": true
		});
		$('#datadocument_list').DataTable({
			"ordering": false
		});
		$(document).on("click", ".image-link", function(e){
			e.preventDefault();
			window.open($(this).attr("href"), '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
		});
		
		if($(".mongo-data-table .views-table").length){
			$(".mongo-data-table .views-table").addClass("display nowrap");
			$(".mongo-data-table .views-table").css("width:100%");
		}
		
		$("input[name$='[select_all]']").click(function(){
			curLevel = $(this).attr("data-attr").split("###");

			if($(this).is(":checked"))
				$('input[data-attr="'+curLevel[1]+'"]').attr("checked",true);
			else
				$('input[data-attr="'+curLevel[1]+'"]').attr("checked",false);
		});
		
		/* document.getElementById('edit-email').addEventListener('invalid', function () {
		  if (this.value.trim() !== '') {
			this.setCustomValidity("'" + this.value + "' is not a valid email bro!");
		  }
		}, false); */
	});
})(jQuery, Drupal);