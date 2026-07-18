<?php 
    session_start();
    include 'config/hospital.php';

    if (!$conn) {
    die("Connection Failed : " . mysqli_connect_error());
}

$view = isset($_GET['view']) ? $_GET['view'] : 'month';
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$patient_id = $_GET['id'];

$timestamp = strtotime($currentDate);

if($view == "day"){

    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 day'));
    $title = date('d M Y', $timestamp);

}
elseif($view == "week"){

    $prevDate = date('Y-m-d', strtotime($currentDate.' -7 day'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +7 day'));

    $weekStart = date('d M', strtotime('monday this week', $timestamp));
    $weekEnd   = date('d M Y', strtotime('sunday this week', $timestamp));

    $title = $weekStart." - ".$weekEnd;

}
else{

    $prevDate = date('Y-m-d', strtotime($currentDate.' -1 month'));
    $nextDate = date('Y-m-d', strtotime($currentDate.' +1 month'));

    $title = date('F Y', $timestamp);

}

switch($view){

    case "day":
        $dateCondition = "DATE(p.created_at)='".date('Y-m-d',$timestamp)."'";
        break;

    case "week":
        $dateCondition = "YEARWEEK(p.created_at,1)=YEARWEEK('$currentDate',1)";
        break;

    default:
        $dateCondition = "MONTH(p.created_at)='".date('m',$timestamp)."'
                          AND YEAR(p.created_at)='".date('Y',$timestamp)."'";
        break;
}


    if (!isset($_SESSION['id'])) {
    header("Location: auth/logout.php");
    exit();
}



    if (!isset($_SESSION['id'])) {
    header("Location: auth/logout.php");
    exit();
}


if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $deleteQuery = "UPDATE prescriptions SET delete_flag = 1 WHERE id = '$delete_id'";
    if ($conn->query($deleteQuery)) {
        echo "<script>
            alert('Prescription deleted successfully!');
            window.location.href='view_prescription_history.php';
        </script>";
        exit();
    }
}


$prescriptionQuery = "
SELECT p.*, pat.patient_name
FROM prescriptions p
LEFT JOIN patients pat
ON p.patient_id = pat.patient_id
WHERE p.patient_id = '$patient_id'
AND (p.delete_flag = 0 OR p.delete_flag IS NULL)
AND $dateCondition
ORDER BY p.created_at DESC";



$prescriptionResult = $conn->query($prescriptionQuery);
$prescriptionCount = $prescriptionResult->num_rows;

 ?>

<!DOCTYPE html><!--Knsq4Bo3YhLaj0lB__YB3-->
<html lang="en">
<!-- Mirrored from medixpro-one.vercel.app/prescriptions by HTTrack Website Copier/3.x [XR&CO'2014], Fri, 26 Jun 2026 11:10:23 GMT -->
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->

<head>
    <meta charSet="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="preload" href="_next/static/media/83afe278b6a6bb3c-s.p.3a6ba036.woff2" as="font" crossorigin=""
        type="font/woff2" />
    <link rel="stylesheet" href="_next/static/chunks/4fbfc6079ef7eaf2.css" data-precedence="next" />
    <link rel="preload" as="script" fetchPriority="low" href="_next/static/chunks/4cac1366d96a9637.js" />
    <script src="_next/static/chunks/ad577b75422c30cd.js" async=""></script>
    <script src="_next/static/chunks/4fa6bd9d1ed5aed0.js" async=""></script>
    <script src="_next/static/chunks/fb95eaa4acb7f0d7.js" async=""></script>
    <script src="_next/static/chunks/turbopack-e6dece88d372121b.js" async=""></script>
    <script src="_next/static/chunks/369eb53031097ae3.js" async=""></script>
    <script src="_next/static/chunks/073f9a8cd1eb88e2.js" async=""></script>
    <script src="_next/static/chunks/c7a9dcf83f6f0796.js" async=""></script>
    <script src="_next/static/chunks/1514599ed994ad3f.js" async=""></script>
    <script src="_next/static/chunks/0034e7e07af7d807.js" async=""></script>
    <script src="_next/static/chunks/66a955e120e99dfe.js" async=""></script>
    <script src="_next/static/chunks/e3e7ecf423ca47d3.js" async=""></script>
    <script src="_next/static/chunks/69cead1afa7d6495.js" async=""></script>
    <script src="_next/static/chunks/9f3f52fd31414b11.js" async=""></script>
    <script src="_next/static/chunks/7cdd9e0aaa07a87d.js" async=""></script>
    <meta name="next-size-adjust" content="" />
    <title>MedixPro - Clinic Management System</title>
    <meta name="description" content="Modern clinic management system for healthcare professionals" />
    <link rel="icon" href="faviconf9c6.ico?favicon.b10a9111.ico" sizes="48x48" type="image/x-icon" />
    <script src="_next/static/chunks/a6dad97d9634a72d.js" noModule=""></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-active { background-color: #f3f4f6; color: #111827; }
        .main-content { margin-left: 260px; padding: 20px 28px; min-height: 100vh; }
        .stat-card { background: white; border-radius: 12px; padding: 20px 24px; border: 1px solid #e5e7eb; transition: all 0.2s ease; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .stat-card .stat-number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: 14px; color: #64748b; font-weight: 500; }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header { padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .card-header h3 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .card-body { padding: 20px 24px; }
        .action-btn { transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 6px; border-radius: 6px; }
        .action-btn:hover { transform: scale(1.05); }
        .action-btn-view { color: #3b82f6; }
        .action-btn-view:hover { background: #dbeafe; }
        .action-btn-edit { color: #8b5cf6; }
        .action-btn-edit:hover { background: #ede9fe; }
        .action-btn-delete { color: #ef4444; }
        .action-btn-delete:hover { background: #fee2e2; }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .back-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .back-btn i {
            font-size: 18px;
            line-height: 1;
        }
    </style>
</head>

<body class="inter_5972bc34-module__OU16Qa__className">
    <div class="flex min-h-screen flex-col bg-gray-50">
        <?php include 'header.php'; ?>
        <div class="flex flex-1 items-start">
            <?php include 'Sidebar.php'; ?>
            <main class="main-content w-full">


               <div class="w-full">

               
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <a href="prescription.php" class="back-btn">
                                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                                </a>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">Prescriptions</h1>
                                    <p class="text-gray-500">Manage patient prescriptions and medications.</p>
                                </div>
                            </div>
                           
                        </div>
                    </div>

                
                    

                    



  <div class="bg-white rounded-xl border shadow-sm p-4 mt-5 mb-5 flex justify-between items-center" style="margin-bottom: 2%;">
  <div class="flex items-center gap-2">

        <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo $prevDate; ?>"
           class="p-2 border rounded-lg hover:bg-gray-100">
           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"> <path d="M15 18l-6-6 6-6"/> </svg>
        </a>

       <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo date('Y-m-d'); ?>" class="px-4 py-2 border rounded-lg hover:bg-gray-100"> <?php date_default_timezone_set("America/New_York"); echo $title; ?></a>

          
   <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=<?php echo $view; ?>&date=<?php echo $nextDate; ?>"
   class="p-2 border rounded-lg hover:bg-gray-100">

<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
fill="none" viewBox="0 0 24 24"
stroke="currentColor" stroke-width="2">
<path d="M9 6l6 6-6 6"/>
</svg>

</a>


    </div>

    <div class="flex rounded-lg border overflow-hidden">

        <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=day&date=<?php echo $currentDate; ?>"
   class="px-4 py-2 <?php echo ($view=='day') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
    Day
</a>

       <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=week&date=<?php echo $currentDate; ?>"
   class="px-4 py-2 <?php echo ($view=='week') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
    Week
</a>
      <a href="view_prescription_history.php?id=<?php echo $patient_id; ?>&view=month&date=<?php echo $currentDate; ?>"
   class="px-4 py-2 <?php echo ($view=='month') ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
    Month
</a>

    </div>

     </div>  
                        <div class="card-header">
                            <h2 class="text-xl font-bold mb-4">

                                <?php

                             
                                    echo "All Prescriptions";
                               ?>

                                </h2>




                                
                            <input type="text" id="searchInput" placeholder="Search prescriptions..." class="w-64 pl-4 pr-4 py-2 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500" onkeyup="searchPrescriptions()">
                        </div>

                        
                        <div class="card-body overflow-x-auto p-4">
                            <?php if ($prescriptionCount > 0): ?>
                           <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50">
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Patient</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Medicine</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Dosage</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Follow-up</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Actions</th>
                                    </tr>
                                </thead>
                                    <tbody id="prescriptionTableBody">
                                    <?php $i = 1; while ($row = $prescriptionResult->fetch_assoc()): ?>
                                    <tr
                                        class="border-b border-gray-100 hover:bg-gray-50 transition-all cursor-pointer"
                                        onclick="window.location='view_prescription.php?id=<?php echo $row['id']; ?>'">

                                        <td class="px-4 py-3"><?php echo $i++; ?></td>
                                        <td class="px-4 py-3 font-medium"><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                                        <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['dosage']); ?></td>
                                        <td class="px-4 py-3">
                                            <?php echo $row['followup_date'] ? date('d-m-Y', strtotime($row['followup_date'])) : '—'; ?>
                                        </td>

                                        <td class="px-4 py-3 text-center" onclick="event.stopPropagation();">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="edit_prescription.php?id=<?php echo $row['id']; ?>"
                                                class="action-btn action-btn-edit"
                                                title="Edit">
                                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                                </a>

                                                <a href="javascript:void(0);"
                                                onclick="confirmDelete(<?php echo $row['id']; ?>)"
                                                class="action-btn action-btn-delete"
                                                title="Delete">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    </tbody>
                           </table>
                            <?php else: ?>
                            <div class="py-12 text-center text-gray-500">
                                <i data-lucide="file-text" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="text-lg font-medium">No prescriptions found</p>
                                <p class="text-sm text-gray-400">Create your first prescription now.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this prescription?")) {
                window.location.href = "view_prescription_history.php?delete_id=" + id;
            }
        }

        function searchPrescriptions() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#prescriptionTableBody tr');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
    </script>
    
    <script>$RB = []; $RV = function (a) { $RT = performance.now(); for (var b = 0; b < a.length; b += 2) { var c = a[b], e = a[b + 1]; null !== e.parentNode && e.parentNode.removeChild(e); var f = c.parentNode; if (f) { var g = c.previousSibling, h = 0; do { if (c && 8 === c.nodeType) { var d = c.data; if ("/$" === d || "/&" === d) if (0 === h) break; else h--; else "$" !== d && "$?" !== d && "$~" !== d && "$!" !== d && "&" !== d || h++ } d = c.nextSibling; f.removeChild(c); c = d } while (c); for (; e.firstChild;)f.insertBefore(e.firstChild, c); g.data = "$"; g._reactRetry && requestAnimationFrame(g._reactRetry) } } a.length = 0 };
        $RC = function (a, b) { if (b = document.getElementById(b)) (a = document.getElementById(a)) ? (a.previousSibling.data = "$~", $RB.push(a, b), 2 === $RB.length && ("number" !== typeof $RT ? requestAnimationFrame($RV.bind(null, $RB)) : (a = performance.now(), setTimeout($RV.bind(null, $RB), 2300 > a && 2E3 < a ? 2300 - a : $RT + 300 - a)))) : b.parentNode.removeChild(b) }; $RC("B:0", "S:0")</script>
    <script>(self.__next_f = self.__next_f || []).push([0])</script>
    <script>self.__next_f.push([1, "1:\"$Sreact.fragment\"\n2:I[340799,[\"/_next/static/chunks/369eb53031097ae3.js\"],\"default\"]\n3:I[345121,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"default\"]\n4:I[760512,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"default\"]\n5:I[48654,[\"/_next/static/chunks/369eb53031097ae3.js\",\"/_next/static/chunks/1514599ed994ad3f.js\",\"/_next/static/chunks/0034e7e07af7d807.js\",\"/_next/static/chunks/66a955e120e99dfe.js\",\"/_next/static/chunks/e3e7ecf423ca47d3.js\",\"/_next/static/chunks/69cead1afa7d6495.js\",\"/_next/static/chunks/9f3f52fd31414b11.js\"],\"DashboardLayout\"]\n6:I[730687,[\"/_next/static/chunks/369eb53031097ae3.js\",\"/_next/static/chunks/1514599ed994ad3f.js\",\"/_next/static/chunks/0034e7e07af7d807.js\",\"/_next/static/chunks/66a955e120e99dfe.js\",\"/_next/static/chunks/e3e7ecf423ca47d3.js\",\"/_next/static/chunks/69cead1afa7d6495.js\",\"/_next/static/chunks/9f3f52fd31414b11.js\"],\"Toaster\"]\n7:I[966863,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"ClientPageRoot\"]\n8:I[898918,[\"/_next/static/chunks/369eb53031097ae3.js\",\"/_next/static/chunks/1514599ed994ad3f.js\",\"/_next/static/chunks/0034e7e07af7d807.js\",\"/_next/static/chunks/66a955e120e99dfe.js\",\"/_next/static/chunks/e3e7ecf423ca47d3.js\",\"/_next/static/chunks/69cead1afa7d6495.js\",\"/_next/static/chunks/9f3f52fd31414b11.js\",\"/_next/static/chunks/7cdd9e0aaa07a87d.js\"],\"default\"]\nb:I[735417,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"OutletBoundary\"]\nc:\"$Sreact.suspense\"\ne:I[735417,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"ViewportBoundary\"]\n10:I[735417,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"MetadataBoundary\"]\n12:I[950025,[],\"default\"]\n:HL[\"/_next/static/chunks/4fbfc6079ef7eaf2.css\",\"style\"]\n:HL[\"/_next/static/media/83afe278b6a6bb3c-s.p.3a6ba036.woff2\",\"font\",{\"crossOrigin\":\"\",\"type\":\"font/woff2\"}]\n"])</script>
    <script>self.__next_f.push([1, "0:{\"P\":null,\"b\":\"Knsq4Bo3YhLaj0lB_-YB3\",\"c\":[\"\",\"prescriptions\"],\"q\":\"\",\"i\":false,\"f\":[[[\"\",{\"children\":[\"(dashboard)\",{\"children\":[\"prescriptions\",{\"children\":[\"__PAGE__\",{}]}]}]},\"$undefined\",\"$undefined\",true],[[\"$\",\"$1\",\"c\",{\"children\":[[[\"$\",\"link\",\"0\",{\"rel\":\"stylesheet\",\"href\":\"/_next/static/chunks/4fbfc6079ef7eaf2.css\",\"precedence\":\"next\",\"crossOrigin\":\"$undefined\",\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-0\",{\"src\":\"/_next/static/chunks/369eb53031097ae3.js\",\"async\":true,\"nonce\":\"$undefined\"}]],[\"$\",\"html\",null,{\"lang\":\"en\",\"children\":[\"$\",\"body\",null,{\"className\":\"inter_5972bc34-module__OU16Qa__className\",\"children\":[\"$\",\"$L2\",null,{\"children\":[\"$\",\"$L3\",null,{\"parallelRouterKey\":\"children\",\"error\":\"$undefined\",\"errorStyles\":\"$undefined\",\"errorScripts\":\"$undefined\",\"template\":[\"$\",\"$L4\",null,{}],\"templateStyles\":\"$undefined\",\"templateScripts\":\"$undefined\",\"notFound\":[[[\"$\",\"title\",null,{\"children\":\"404: This page could not be found.\"}],[\"$\",\"div\",null,{\"style\":{\"fontFamily\":\"system-ui,\\\"Segoe UI\\\",Roboto,Helvetica,Arial,sans-serif,\\\"Apple Color Emoji\\\",\\\"Segoe UI Emoji\\\"\",\"height\":\"100vh\",\"textAlign\":\"center\",\"display\":\"flex\",\"flexDirection\":\"column\",\"alignItems\":\"center\",\"justifyContent\":\"center\"},\"children\":[\"$\",\"div\",null,{\"children\":[[\"$\",\"style\",null,{\"dangerouslySetInnerHTML\":{\"__html\":\"body{color:#000;background:#fff;margin:0}.next-error-h1{border-right:1px solid rgba(0,0,0,.3)}@media (prefers-color-scheme:dark){body{color:#fff;background:#000}.next-error-h1{border-right:1px solid rgba(255,255,255,.3)}}\"}}],[\"$\",\"h1\",null,{\"className\":\"next-error-h1\",\"style\":{\"display\":\"inline-block\",\"margin\":\"0 20px 0 0\",\"padding\":\"0 23px 0 0\",\"fontSize\":24,\"fontWeight\":500,\"verticalAlign\":\"top\",\"lineHeight\":\"49px\"},\"children\":404}],[\"$\",\"div\",null,{\"style\":{\"display\":\"inline-block\"},\"children\":[\"$\",\"h2\",null,{\"style\":{\"fontSize\":14,\"fontWeight\":400,\"lineHeight\":\"49px\",\"margin\":0},\"children\":\"This page could not be found.\"}]}]]}]}]],[]],\"forbidden\":\"$undefined\",\"unauthorized\":\"$undefined\"}]}]}]}]]}],{\"children\":[[\"$\",\"$1\",\"c\",{\"children\":[[[\"$\",\"script\",\"script-0\",{\"src\":\"/_next/static/chunks/1514599ed994ad3f.js\",\"async\":true,\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-1\",{\"src\":\"/_next/static/chunks/0034e7e07af7d807.js\",\"async\":true,\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-2\",{\"src\":\"/_next/static/chunks/66a955e120e99dfe.js\",\"async\":true,\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-3\",{\"src\":\"/_next/static/chunks/e3e7ecf423ca47d3.js\",\"async\":true,\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-4\",{\"src\":\"/_next/static/chunks/69cead1afa7d6495.js\",\"async\":true,\"nonce\":\"$undefined\"}],[\"$\",\"script\",\"script-5\",{\"src\":\"/_next/static/chunks/9f3f52fd31414b11.js\",\"async\":true,\"nonce\":\"$undefined\"}]],[\"$\",\"$L5\",null,{\"children\":[[\"$\",\"$L6\",null,{\"richColors\":true,\"position\":\"top-right\"}],[\"$\",\"$L3\",null,{\"parallelRouterKey\":\"children\",\"error\":\"$undefined\",\"errorStyles\":\"$undefined\",\"errorScripts\":\"$undefined\",\"template\":[\"$\",\"$L4\",null,{}],\"templateStyles\":\"$undefined\",\"templateScripts\":\"$undefined\",\"notFound\":[[[\"$\",\"title\",null,{\"children\":\"404: This page could not be found.\"}],[\"$\",\"div\",null,{\"style\":\"$0:f:0:1:0:props:children:1:props:children:props:children:props:children:props:notFound:0:1:props:style\",\"children\":[\"$\",\"div\",null,{\"children\":[[\"$\",\"style\",null,{\"dangerouslySetInnerHTML\":{\"__html\":\"body{color:#000;background:#fff;margin:0}.next-error-h1{border-right:1px solid rgba(0,0,0,.3)}@media (prefers-color-scheme:dark){body{color:#fff;background:#000}.next-error-h1{border-right:1px solid rgba(255,255,255,.3)}}\"}}],[\"$\",\"h1\",null,{\"className\":\"next-error-h1\",\"style\":\"$0:f:0:1:0:props:children:1:props:children:props:children:props:children:props:notFound:0:1:props:children:props:children:1:props:style\",\"children\":404}],[\"$\",\"div\",null,{\"style\":\"$0:f:0:1:0:props:children:1:props:children:props:children:props:children:props:notFound:0:1:props:children:props:children:2:props:style\",\"children\":[\"$\",\"h2\",null,{\"style\":\"$0:f:0:1:0:props:children:1:props:children:props:children:props:children:props:notFound:0:1:props:children:props:children:2:props:children:props:style\",\"children\":\"This page could not be found.\"}]}]]}]}]],[]],\"forbidden\":\"$undefined\",\"unauthorized\":\"$undefined\"}]]}]]}],{\"children\":[[\"$\",\"$1\",\"c\",{\"children\":[null,[\"$\",\"$L3\",null,{\"parallelRouterKey\":\"children\",\"error\":\"$undefined\",\"errorStyles\":\"$undefined\",\"errorScripts\":\"$undefined\",\"template\":[\"$\",\"$L4\",null,{}],\"templateStyles\":\"$undefined\",\"templateScripts\":\"$undefined\",\"notFound\":\"$undefined\",\"forbidden\":\"$undefined\",\"unauthorized\":\"$undefined\"}]]}],{\"children\":[[\"$\",\"$1\",\"c\",{\"children\":[[\"$\",\"$L7\",null,{\"Component\":\"$8\",\"serverProvidedParams\":{\"searchParams\":{},\"params\":{},\"promises\":[\"$@9\",\"$@a\"]}}],[[\"$\",\"script\",\"script-0\",{\"src\":\"/_next/static/chunks/7cdd9e0aaa07a87d.js\",\"async\":true,\"nonce\":\"$undefined\"}]],[\"$\",\"$Lb\",null,{\"children\":[\"$\",\"$c\",null,{\"name\":\"Next.MetadataOutlet\",\"children\":\"$@d\"}]}]]}],{},null,false,false]},[null,[],[]],false,false]},null,false,false]},null,false,false],[\"$\",\"$1\",\"h\",{\"children\":[null,[\"$\",\"$Le\",null,{\"children\":\"$Lf\"}],[\"$\",\"div\",null,{\"hidden\":true,\"children\":[\"$\",\"$L10\",null,{\"children\":[\"$\",\"$c\",null,{\"name\":\"Next.Metadata\",\"children\":\"$L11\"}]}]}],[\"$\",\"meta\",null,{\"name\":\"next-size-adjust\",\"content\":\"\"}]]}],false]],\"m\":\"$undefined\",\"G\":[\"$12\",[]],\"S\":true}\n"])</script>
    <script>self.__next_f.push([1, "9:{}\na:\"$0:f:0:1:1:children:1:children:1:children:0:props:children:0:props:serverProvidedParams:params\"\n"])</script>
    <script>self.__next_f.push([1, "f:[[\"$\",\"meta\",\"0\",{\"charSet\":\"utf-8\"}],[\"$\",\"meta\",\"1\",{\"name\":\"viewport\",\"content\":\"width=device-width, initial-scale=1\"}]]\n"])</script>
    <script>self.__next_f.push([1, "13:I[774534,[\"/_next/static/chunks/073f9a8cd1eb88e2.js\",\"/_next/static/chunks/c7a9dcf83f6f0796.js\"],\"IconMark\"]\nd:null\n11:[[\"$\",\"title\",\"0\",{\"children\":\"MedixPro - Clinic Management System\"}],[\"$\",\"meta\",\"1\",{\"name\":\"description\",\"content\":\"Modern clinic management system for healthcare professionals\"}],[\"$\",\"link\",\"2\",{\"rel\":\"icon\",\"href\":\"faviconf9c6.ico?favicon.b10a9111.ico\",\"sizes\":\"48x48\",\"type\":\"image/x-icon\"}],[\"$\",\"$L13\",\"3\",{}]]\n"])</script>
</body>

</html>