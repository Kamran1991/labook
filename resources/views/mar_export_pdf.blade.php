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
		}
		#customers td.paid {
			color: #818CC5;
			font-size:48px;
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
			margin-left: -1em;
		}
	</style>
</head>
<body>

@foreach ($data as $d)
<table id="customers">
	<h3>{{ \Carbon\Carbon::parse($d['date'])->format('d F | D')}}</h3>
	<tr class="counts">
		<td class="present">{{$d['present']}}</td>
		<td class="paid">{{$d['paid_holiday']}}</td>
		<td class="absent">{{$d['absent']}}</td>
		<td class="halfday">{{$d['halfday']}}</td>
	</tr>
	<tr>
		<td>Present</td>
		<td>Paid Holiday</td>
		<td>Absent</td>
		<td>Half Day</td>
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
