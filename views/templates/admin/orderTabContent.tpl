<div class="tab-pane d-print-block fade" id="boekuwzendingTabContent" role="tabpanel" aria-labelledby="boekuwzendingTab">
	{if $orders}
	<table class="table">
		<thead>
		<tr>
			<th>{l s='Date created' d='Modules.Boekuwzending.Adminordertabcontent'}</th>
			<th>{l s='Link' d='Modules.Boekuwzending.Adminordertabcontent'}</th>
		</tr>
		</thead>
		<tbody>
		{foreach $orders as $order}
		<tr>
			<td>{$order->getLocalCreated()}</td>
			<td><a href="{$baseUrl|replace:'{id}':$order->getBoekuwzendingId()}" target="_blank">{l s='View order' d='Modules.Boekuwzending.Adminordertabcontent'}</a></td>
		</tr>
		{/foreach}
		</tbody>
	</table>
	{else}
		<p>{l s='Order not known at Boekuwzending.' d='Modules.Boekuwzending.Adminordertabcontent'}</p>
	{/if}
	<form method="POST" action="{url entity='sf' route='boekuwzending_order_create' sf-params=[ 'orderId' => $orderId ]}">
		<button class="btn btn-primary">
			{if $orders}
				{l s='Create new order' d='Modules.Boekuwzending.Adminordertabcontent'}
			{else}
				{l s='Create order' d='Modules.Boekuwzending.Adminordertabcontent'}
			{/if}
		</button>
	</form>
</div>