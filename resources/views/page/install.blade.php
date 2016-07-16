@extends('layout')

@section('content')
<div class="title">Install to Shopify</div>
<form action="" method="post">
	<label for='shop'><strong>The URL of the Shop</strong>
		<span class="hint">(enter it exactly like this: myshop.myshopify.com)</span>
	</label>
	<p>
		{{ csrf_field() }}
		<input id="shop" name="shop" size="45" type="text" value="" />
		<input name="commit" type="submit" value="Install" />
	</p>
</form>
@stop