(function($) { 
	$(document).ready(function() {

	jQuery('#wds-accordion-slider').easyAccordion({
			autoStart: true,
			slideInterval: 6000,
			slideNum:false
	});
	
	equal_row_height();
	
	//this add an onclick event to the "New Topic" button while preserving the original event; this is so "New Topic" can have a "current" class
	$('.show-hide-new').click (function (){
            var origOnClick = $('.show-hide-new').onclick;
            return function (e) {
                if (origOnClick != null && !origOnClick()) {
                    return false;
                }
                alert('some work');
                return true;
			}
			});
	
	//this is for the filtering - changes the text class to a "red" state
	$('#group_seq_form select').change(function(){
												
												$(this).removeClass('gray-text');
												$(this).addClass('red-text');
												$(this).prev('div.gray-square').addClass('red-square').removeClass('gray-square');

												});
	
	//ajax functionality for courses archive
	$('#school-select').change(function(){
	  var str = $(this).val();
	  console.log(str);
	  if (str=="") {
		document.getElementById("dept-select").innerHTML="";
		return;
	  }
	  
	  $.ajax({
			 type: 'POST',
			 url: 'http://' + document.domain + '/wp-admin/admin-ajax.php',
			 data:
			  {
				  action: 'openlab_ajax_return_course_list',
				  str: str,
			  },
			  success: function(data, textStatus, XMLHttpRequest)
			  {
				  $('#dept-select').html(data);
			  },
			  error: function(MLHttpRequest, textStatus, errorThrown){  
				  console.log(errorThrown);
			  }
			 });
										});
  function clear_form(){
	  document.getElementById('group_seq_form').reset();
  }

	});//end document.ready
	
	/*this is for the homepage group list, so that cells in each row all have the same height 
	- there is a possiblity of doing this template-side, but requires extensive restructuring of the group list function*/
	function equal_row_height()
	{
	/*first we get the number of rows by finding the column with the greatest number of rows*/
	var $row_num = 0;
	$('.activity-list').each(function(){
									 
									  $row_check = $(this).find('.row').length;
									  
									  if ($row_check > $row_num)
									  {
										  $row_num = $row_check;
									  }
									  
									  });
	
	//build a loop to iterate through each row
	$i = 1;
	  while ($i <= $row_num)
	  {
		  //check each cell in the row - find the one with the greatest height
		  var $greatest_height = 0;
		  $('.row-'+$i).each(function(){
									 
									 $cell_height = $(this).height();
									 
									 if ($cell_height > $greatest_height)
									 {
										 $greatest_height = $cell_height;
									 }
									 
									 });
		  
		  //now apply that height to the other cells in the row
		  $('.row-'+$i).css('height',$greatest_height + 'px');
		  
		  //iterate to next row
		  $i++;
	  }
	  
	//there is an inline script that hides the lists from the user on load (just so the adjusment isn't jarring) - this will show the lists
	$('.activity-list').css('visibility','visible');
		
	}
	
})(jQuery);