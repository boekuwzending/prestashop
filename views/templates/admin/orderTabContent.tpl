<div class="tab-pane d-print-block fade" id="boekuwzendingTabContent" role="tabpanel" aria-labelledby="boekuwzendingTab">
	{if $orders}
	<table class="table">
		<thead>
		<tr>
			<th>Date created</th>
			<th>Link</th>
		</tr>
		</thead>
		<tbody>
		{foreach $orders as $order}
		<tr>
			<td>{$order->getLocalCreated()}</td>
			<td><a href="{$baseUrl|replace:'{id}':$order->getBoekuwzendingId()}" target="_blank">View order</a></td>
		</tr>
		{/foreach}
		</tbody>
	</table>
	{else}
		<p>{l s="Order not known at Boekuwzending." mod='boekuwzending'}</p>
	{/if}
	<form method="POST" action="{url entity="sf" route="boekuwzending_order_create" sf-params=[ "orderId" => $orderId]}">
		<button class="btn btn-primary">
			{if $orders}
				{l s="Create new order" mod='boekuwzending'}
			{else}
				{l s="Create order" mod='boekuwzending'}
			{/if}
		</button>
	</form>
</div>