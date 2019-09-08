<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Link {{ $durc_type_left }} {{$durc_type_right}}</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/select2.min.css" rel="stylesheet" />
<style>

ul.select2-results__options { max-height: 700px !important; }

</style>


  </head>
  <body>

    <div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="jumbotron">
				<h2>
					Generate new links between {{$durc_type_left}} and {{$durc_type_right}}
				</h2>
				<p>
This interface allows you to quickly tag sets of objects...
<br> <a target='_blank' href='/Zermelo/DURC_{{$durc_type_left}}_{{$durc_type_right}}_{{$durc_type_tag}}'> Current List of Links </a>
				</p>
			</div>

		</div>
	</div>
     </div>
<div class='container-fluid'>

<form method='POST' action='/genericLinkerSave/{{$durc_type_left}}/{{$durc_type_right}}/{{$durc_type_tag}}'>
	{{ csrf_field() }}

	<div class='row'>
		<div class='col-md-4'>
			<h3> {{$durc_type_left}} </h3>
			<a href='/DURC/{{$durc_type_left}}' target='_blank'>Add {{$durc_type_left}} entries</a><br>

			<select class='{{$durc_type_left}}_id form-control' multiple='' id='{{$durc_left_id}}' name='{{$durc_left_id}}[]'>
			</select>

		</div>

		<div class='col-md-4'>
			<h3> {{$durc_type_tag}} </h3>
			<a href='/DURC/{{$durc_type_tag}}' target='_blank'>Add {{$durc_type_tag}} entries</a><br>

			<select class='{{$durc_type_tag}}_id form-control' multiple='' id='{{$durc_tag_id}}' name='{{$durc_tag_id}}[]'>
			</select>

		</div>
		<div class='col-md-4'>
			<h3> {{$durc_type_right}} </h3>
			<a href='/DURC/{{$durc_type_right}}' target='_blank'>Add {{$durc_type_right}} entries</a><br>

			<select class='{{$durc_right_id}} form-control' multiple='' id='{{$durc_right_id}}' name='{{$durc_right_id}}[]'>
			</select>

		</div>
	</div>
</div>
<div class='container'>
	<div class='row'>
		<div class='col-md-12'>
			<br><br>

  <div class="form-group row">
    <div class="col-2"></div>
    <label for="link_note" class="col-2 col-form-label"><h3>Link Notes</h3></label> 
    <div class="col-8">
      <textarea id="link_note" name="link_note" cols="40" rows="5" aria-describedby="link_notesHelpBlock" class="form-control"></textarea> 
      <span id="link_notesHelpBlock" class="form-text text-muted">Make any notes about these links here</span>
    </div>
  </div> 
  <div class="form-group row">
    <div class="offset-4 col-8">
      <button name="submit" type="submit" class="btn btn-primary">Save Links</button>
    </div>
  </div>
		</div>
	</div>
	</form>

</div>

    <script src="/js/jquery-3.3.1.min.js"></script>
    <script src="/js/popper.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/select2.min.js"></script>

<script type='text/javascript'>


function token_template(search_result,container,query) {
    if (search_result.img_url === undefined) return search_result.text; // when there is no image.. just return the text
	//if we get here then img_url was notnull and we want to use it to make an image
    return "<img width='150px' src='"+search_result.img_url+"'/> " + search_result.text;

}


$('.{{$durc_left_id}}').select2({
  ajax: {
    	url: '/DURC/searchjson/{{$durc_type_left}}/',
    	dataType: 'json'
  },
    minimumInputLength: 3,
    templateResult: token_template,
    templateSelection: token_template,
    escapeMarkup: function(m) { return m; }	
});

$('.{{$durc_right_id}}').select2({
  ajax: {
    	url: '/DURC/searchjson/{{$durc_type_right}}/',
    	dataType: 'json'
  },
    minimumInputLength: 2,
    templateResult: token_template,
    templateSelection: token_template,
    escapeMarkup: function(m) { return m; }	
});

$('.{{$durc_tag_id}}').select2({
  ajax: {
    	url: '/DURC/searchjson/{{$durc_type_tag}}/',
    	dataType: 'json'
  },
    templateResult: token_template,
    templateSelection: token_template,
    escapeMarkup: function(m) { return m; }	
});

</script>
  </body>
</html>
