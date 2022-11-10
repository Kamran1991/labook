<<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/css/bootstrap.min.css">
	<title>this is PDF</title>
	<style>
		#customers {
			font-family: Arial, Helvetica, sans-serif;
			border-collapse: collapse;
			width: 100%;
			margin-left: 50px;
		}

		#customers td, #customers th {
		    border: 1px solid #ddd;
		    padding: 8px;
		}


		#customers tr {
			padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			border:none;
			font-size:16px;
			font-weight:600;
		}
		#customers tr.counts {
			font-weight:400;
		}

		#customers td {
		 	border : none;
		}

		#customers td.present {
			color: #04AA6D;
			font-size:48px;
			padding-left: 24px;
		}
		#customers td.paid {
			color: #818CC5;
			font-size:48px;
			padding-left: 24px;
		}

		#customers td.absent {
		  	color: #E4918E;
		  	font-size:48px;
		}

		#customers td.halfday {
		  	color: #CFB863;
		  	font-size:48px;
		}
		ul {
		    list-style: none;
		    text-align: left;
    		list-style-position: outside;
		}
		ul li::before {
		    content: "\2022";
		    color: #04AA6D;
		    font-weight: bold;
		    font-size: 24px;
		    display: inline-block; 
		    width: 1em;
			margin-left: 1em;
		}
		.logo {
			margin-left:-2rem;
			margin-bottom:1rem;
			margin-top:-2rem;
		}
	</style>
</head>
<body>
<div class="logo">
	<img src="{{ public_path('images/folder.jpeg') }}" alt="logo" width="80" height="80">
</div>
@foreach ($data as $d)
<h3>{{ \Carbon\Carbon::parse($d['date'])->format('d F | D')}}</h3>
<table id="customers">
	<tr class="counts">
		<td class="present">{{$d['present']}}</td>
		<td class="paid">{{$d['paid_holiday']}}</td>
		<td></td>
		<td class="absent">{{$d['absent']}}</td>
		<td class="halfday">{{$d['halfday']}}</td>
	</tr>
	<tr>
		<td style="width: 20%;">Present</td>
		<td style="width: 20%;">Paid Holiday</td>
		<td style="width: 20%;"></td>
		<td style="width: 20%;">Absent</td>
		<td style="width: 20%;">Half Day</td>
	</tr>
</table>

<ul>
	@foreach ($d['present_list'] as $pl)
		<li>{{ $pl->name }}</li> 
	@endforeach
</ul>

<div class="col-lg-12 my-5" style="border-bottom: 1px solid #ccc;"></div>
@endforeach
<br/><br/>
</body>
</html>
