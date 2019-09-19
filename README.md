# Xhprof Admin
This tool is based on original GUI for Xhprof from Facebook. 

It allows to:
1. Compare more then two profile runs at the same time without restriction about 
the same namespace (against of the same namesapace of two runs only in native Xhprof GUI)
2. Set custom comments for runs
3. Delete chosen runs files directly from new GUI
4. Have better navigation to native reports (diff, aggregate)  

## Prerequisites
- PHP 5.6 or latter

## Installation
1. To install Xhprof see original documentation in **/xhprof_html/docs/** folder
2. To use opportunities of new GUI just open address **/xhprof_html/xhprof_admin/**. You should open **/xhprof_admin/** if mapping is set to 
**/xhprof_html/** folder.

## Admin page
On this page you can see all profile sessions (which are referred to as a runs) sorted by **file date** with 
all possible operations and drill down links. 

There are links to:
* new full report 
* original Xhprof GUI report  
* original Xhprof callgraph 

## Compare report
To compare two and more runs:
1. Select runs to compare on **admin page**.
2. Set sort value to define order in which runs will be printed in table. The least value will be the base run.
3. Check **calc average** if you need to print average values of each function metric.
4. Click **Compare runs** 

![N|Solid](https://www.uchitel-izd.ru/upload/files/clip2net/ol/2019/09.19-1463.png )

The view of report:
![N|Solid](https://www.uchitel-izd.ru/upload/files/clip2net/ol/2019/09.19-1940.png)
Each metric column is separated with bold line and consists of runs columns.
 
You can drill down into function report of a run by clicking on the cell in sorted metric column.

To sort by metric just click on appropriate column.

Only top 100 functions are printed by default. To display all functions click **display all**.  

Improvements are marked with green color, regressions with red. 

Click to **View all available runs** to return to the **Admin page**.

Click to **Show/hide average values** to add/remove average metric in the table.

To exclude run from report click on **exclude** in **Compared runs info** table.

## Custom comments
You can set custom comments to each run on **Admin Page** by typing in **custom comment** column and clicking on 
**Save custom comment**.

## Deleting runs
Just check runs to delete and click on **Delete selected runs**.
It will delete run files with run comment files.