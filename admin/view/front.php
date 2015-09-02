<h1><i class="fa fa-dashboard"></i> Dashboard</h1>
<span class="space h10"></span>
<span>Welcome back, <?php echo $user['forename']; ?> <?php echo $user['surname']; ?>!</span>
<span class="space h20"></span>
<section class="gcontainer">
	<section class="col col1 w100">
		<section class="block">
			<div class="box min grey-bg">
				<section class="gcontainer">
					<section class="col col3 w33">
						<section class="block">
							<h2>Deposits: <span class="bubble price"><?php echo formatPrice($totalDeposits, true); ?></span></h2>
						</section>
					</section>
					<section class="col col3 w33">
						<section class="block center">
							<h2>Withdrawals: <span class="bubble price red"><?php echo formatPrice($totalWithdrawals, true); ?></span></h2>
						</section>
					</section>
					<section class="col col3 w33">
						<section class="block right">
							<h2>Balance: <span class="bubble price blue"><?php echo formatPrice($totalDeposits-$totalWithdrawals, true); ?></span></h2>
						</section>
					</section>
					<span class="clear"></span>
				</section>
				<span class="space h10"></span>
			</div>
		</section>
	</section>
	<span class="clear space h20"></span>
	<section class="col col2 w49">
		<section class="block">
			<div class="box min grey-bg">
				<section class="gcontainer">
					<section class="col col2 w70">
						<section class="block">
							<h2><i class="fa fa-bank"></i> &nbsp; Withdrawal Requests <span class="bubble red"><?php echo $getWithdrawals->GetTotalRows(); ?></span></h2>
						</section>
					</section>
					<section class="col col2 w30">
						<section class="block right">
							<h2><a href="<?php echo $mainURL.ADMIN_PATH.'/withdraw-requests'; ?>" class="btn">View All</a></h2>
						</section>
					</section>
					<span class="clear"></span>
				</section>
				<span class="space h10"></span>
				<table class="table_data" width="100%">
				<thead>
				<tr>
				<th>Name</th>
				<th class="right">Amount</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$getWithdrawals->SetPaging(5, 1);
				if ($getWithdrawals->LoadRecords()) {
					while ($getWithdrawals->read()) {
						$loadUser = new Users();
						$loadUser->LoadRecord($getWithdrawals->f('user_id'));
						?>
						<tr>
						<td><a href="<?php echo $mainURL.ADMIN_PATH.'/users/view/'.$loadUser->f('id'); ?>" class="name"><?php echo $loadUser->f('forename'); ?> <?php echo $loadUser->f('surname'); ?></a><br /><small><?php echo $loadUser->f('email'); ?></small></td>
						<td width="120" align="right" style="color:#cc0000"><?php echo $site['left_symbol'].$getWithdrawals->f('amount').$site['right_symbol'].' '.$site['currency']; ?></td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
					<td colspan="2">Sorry, no withdrawal requests could be found!</td>
					</tr>
					<?php
				}
				$getWithdrawals->FreeResult();
				?>
				</tbody>
				</table>
			</div>
		</section>
	</section>
	<section class="col col2 w2">
		<section class="block">&nbsp;</section>
	</section>
	<section class="col col2 w49">
		<section class="block">
			<div class="box min grey-bg">
				<section class="gcontainer">
					<section class="col col2 w70">
						<section class="block">
							<h2><i class="fa fa-credit-card"></i> &nbsp; Latest Deposits <span class="bubble"><?php echo $getDeposits->GetTotalRows(); ?></span></h2>
						</section>
					</section>
					<section class="col col2 w30">
						<section class="block right">
							<h2><a href="<?php echo $mainURL.ADMIN_PATH.'/deposits'; ?>" class="btn">View All</a></h2>
						</section>
					</section>
					<span class="clear"></span>
				</section>
				<span class="space h10"></span>
				<table class="table_data" width="100%">
				<thead>
				<tr>
				<th>Name</th>
				<th class="right">Amount</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$getDeposits->SetPaging(5, 1);
				if ($getDeposits->LoadRecords()) {
					while ($getDeposits->read()) {
						$loadUser = new Users();
						$loadUser->LoadRecord($getDeposits->f('user_id'));
						?>
						<tr>
						<td><a href="<?php echo $mainURL.ADMIN_PATH.'/users/edit/'.$loadUser->f('id'); ?>" class="name"><?php echo $loadUser->f('forename'); ?> <?php echo $loadUser->f('surname'); ?></a><br /><small><?php echo $loadUser->f('email'); ?></small></td>
						<td width="120" align="right" style="color:#cc0000"><?php echo $site['left_symbol'].$getDeposits->f('payment_amount').$site['right_symbol'].' '.$site['currency']; ?></td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
					<td colspan="2">Sorry, no withdrawal requests could be found!</td>
					</tr>
					<?php
				}
				$getWithdrawals->FreeResult();
				?>
				</tbody>
				</table>
			</div>
		</section>
	</section>
	<span class="clear space h20"></span>
	<section class="col col1 w100">
		<section class="block">
			<div class="box min grey-bg">
				<section class="gcontainer">
					<section class="col col2 w50">
						<section class="block">
							<h2><i class="fa fa-users"></i> &nbsp; Newest Members <span class="bubble"><?php echo $getUsers->GetTotalRows(); ?></span></h2>
						</section>
					</section>
					<section class="col col2 w50">
						<section class="block right">
							<h2><a href="<?php echo $mainURL.ADMIN_PATH.'/users'; ?>" class="btn">View All</a></h2>
						</section>
					</section>
					<span class="clear"></span>
				</section>
				<span class="space h10"></span>
				<table class="table_data" width="100%">
				<thead>
				<tr>
				<th width="60">ID</th>
				<th>Name / Email</th>
				<th>IP Address</th>
				<th class="right">Credits</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$getUsers->SetPaging(10, 1);
				if ($getUsers->LoadRecords()) {
					while ($getUsers->read()) {
						?>
						<tr>
						<td><?php echo $getUsers->f('id'); ?></td>
						<td><a href="<?php echo $mainURL.ADMIN_PATH.'/users/edit/'.$loadUser->f('id'); ?>" class="name"><?php echo $getUsers->f('forename'); ?> <?php echo $getUsers->f('surname'); ?></a><br /><small><?php echo $getUsers->f('email'); ?></small></td>
						<td><?php echo $getUsers->f('ip_address'); ?></td>
						<td align="right"><?php echo $getUsers->f('credits'); ?></td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
					<td colspan="2">Sorry, no categories could be found!</td>
					</tr>
					<?php
				}
				$getUsers->FreeResult();
				?>
				</tbody>
				</table>
			</div>
		</section>
	</section>
	<span class="clear"></span>
</section>