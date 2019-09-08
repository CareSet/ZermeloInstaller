<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Link Created</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">

  </head>
  <body>

    <div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="jumbotron">
				<h2>
					{{$total_links_created}} Link(s) created/updated
				</h2>
				<p>
<a href='{{$go_back_url}}'>Create new links</a>
				</p>
			</div>
		</div>
	</div>
</div>

    <script src="/js/jquery-3.4.1.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
  </body>
</html>
