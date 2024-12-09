import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IssuesService } from '../../../Services/issues.service';
import { Issue } from '../../../Models/issue';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import { ClockService } from '../../../Services/clock.service';
import { IssueNotificationService } from '../../../Services/issue-notification.service';

@Component({
  selector: 'app-hr-issues',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hr-issues.component.html',
  styleUrl: './hr-issues.component.css'
})
export class HrIssuesComponent {

  tableData: Issue[] = []
  isModalOpen = false;
  selectedRow: any = null;
  SelectedDate: string = ""
  isDateSelected = false
  PagesNumber: number = 1;
  CurrentPageNumber: number = 1;
  pages: number[] = [];
  DisplayPagginationOrNot: boolean = true;
  selectedMonth: string = "01";
  selectedYear: number = 0;
  DateString: string = "2019-01";
  count:number=0;

  months = [
    { name: 'January', value: "01" },
    { name: 'February', value: "02" },
    { name: 'March', value: "03" },
    { name: 'April', value: "04" }, 
    { name: 'May', value: "05" },
    { name: 'June', value: "06" },
    { name: 'July', value: "07" },
    { name: 'August', value: "08" },
    { name: 'September', value: "09" },
    { name: 'October', value: "10" },
    { name: 'November', value: "11" },
    { name: 'December', value: "12" }
  ];
  constructor(public router: Router, public activeRoute: ActivatedRoute, public issueService: IssuesService, public ClockServ: ClockService ,private issueNotificationService: IssueNotificationService) { }
  years: number[] = [];

  ngOnInit(): void {
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1
    this.selectedMonth = currentMonth < 10 ? `0${currentMonth}` : `${currentMonth}`;
    this.selectedYear = currentDate.getFullYear();
    this.DateString = this.selectedYear + "-" + this.selectedMonth

    this.getAllIssues(1);
    this.populateYears();

  }

  onMonthChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedMonth = target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
      this.tableData = []
      this.getAllIssues(1);    }
  }

  getAllIssues(n:number) {
    this.issueService.getall(n,this.DateString).subscribe(
      (d: any) => {
        // console.log(d);
        // this.count=d.data.count;
        this.sendNotification(1);
        this.tableData = d.data.clockIssues.data
        this.PagesNumber=d.data.clockIssues.pagination.last_page
        this.CurrentPageNumber=n;
        this.generatePages(); 
        this.DisplayPagginationOrNot = true;
      },
      (error) => {
        this.tableData = [];
        this.PagesNumber = 1;
        this.DisplayPagginationOrNot = false;
      }

    );
  }



  ConfirmIssue(id: number) {
    Swal.fire({
      title: 'Are you sure you want to save this clock out and remove issue?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#17253E',
      confirmButtonText: 'Confirm',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        this.issueService.UpdateIssue(id).subscribe(
          (response: any) => {
            this.getAllIssues(this.CurrentPageNumber);
          },
          (error: any) => {
            console.error('Error during issue update:', error);
          }
        );
      }
    });
  }



  searchByDate(){
    this.isDateSelected=true
    this.issueService.searchByDate(this.SelectedDate).subscribe(
      (d: any) => {
        this.tableData = d.clockIssues
        this.PagesNumber = 1;
        this.DisplayPagginationOrNot = false;

      },
      (error) => {
        this.tableData = [];
        this.PagesNumber = 1;
        this.DisplayPagginationOrNot = false;
      }

    );
  }

  // convertUtcToEgyptianTime(utcTime: string): string {
  //   // Regex to match 24-hour format (HH:mm:ss)
  //   const timeRegex = /^(\d{2}):(\d{2}):(\d{2})$/;
  //   const match = utcTime.match(timeRegex);
  
  //   if (!match) {
  //     throw new Error('Invalid time format. Expected format is HH:mm:ss.');
  //   }
  
  //   // Use destructuring and convert matched groups to numbers
  //   let [, hoursStr, minutesStr, secondsStr] = match;
  //   const hours = Number(hoursStr);
  //   const minutes = Number(minutesStr);
  //   const seconds = Number(secondsStr);
  
  //   // Get the current date and set the parsed UTC time
  //   const currentDate = new Date();
  //   const utcDate = new Date(Date.UTC(currentDate.getUTCFullYear(), currentDate.getUTCMonth(), currentDate.getUTCDate(), hours, minutes, seconds));
  
  //   // Convert to Egypt local time
  //   const egyptTimeZone = 'Africa/Cairo';
  //   const localDate = new Date(utcDate.toLocaleString('en-US', { timeZone: egyptTimeZone }));
  
  //   // Convert local time to 12-hour format with AM/PM
  //   let localHours = localDate.getHours();
  //   const localMinutes = String(localDate.getMinutes()).padStart(2, '0');
  //   const periodSuffix = localHours >= 12 ? 'PM' : 'AM';
  
  //   // Adjust hours for 12-hour format
  //   localHours = localHours % 12 || 12; // Convert 0 to 12 for midnight, and 13+ to 1-12
  
  //   // Return the formatted time in hh:mm AM/PM format
  //   return `${localHours}:${localMinutes} ${periodSuffix}`;
  // }
  

  formatTime(timeString: string): string {
    // Split the input time string by colon
    const [hours, minutes] = timeString.split(':').map(Number);
  
    // Convert to 12-hour format
    const formattedHours = hours % 12 || 12;
    const formattedMinutes = minutes.toString().padStart(2, '0');
    
    // Determine AM/PM period
    const localPeriod = hours >= 12 ? 'PM' : 'AM';
  
    // Return formatted time
    return `${formattedHours}:${formattedMinutes} ${localPeriod}`;
  }
  

  openModal(row: any) {
    this.selectedRow = { ...row };  // Copy the row data to avoid modifying the original before saving
    this.isModalOpen = true;
  }

  closeModal() {
    this.isModalOpen = false;
    this.selectedRow = null;
  }

  async SaveData(userid: number, clockId: number, clockOut: string) {
    const { endTime, dateOfIssue } = this.selectedRow;

    const newclockOut = `${dateOfIssue} ${endTime}`;

    // console.log(newclockOut)
    const utcClockOut = newclockOut;
    // console.log(utcClockOu


    this.ClockServ.UpdateUserClockOut(userid, clockId, utcClockOut).subscribe(
      (response: any) => {
        this.closeModal();
        this.issueService.UpdateIssue(clockId).subscribe(
          (response: any) => {
            this.getAllIssues(this.CurrentPageNumber);
          },
          (error: any) => {
            console.error('Error during issue update:', error);
          }
        );
      },
      (error: any) => {
        console.error('Error updating Clock Out:', error.error.message);

        if (error.error.message === 'Clock-out must be on the same day as clock-in.') {
          Swal.fire({
            text: "Error: Clock-out must be on the same day as clock-in.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          }).then(() => {
            this.closeModal();
          });
        } else {
          Swal.fire({
            text: "An error occurred while updating clock out.",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
          }).then(() => {
            this.closeModal();
          });
        }
      }
    );
  }


  // transformEgyptTimeToUTC(egyptDateTime: string): string {
  //   // Parse the input Egypt local datetime string to a Date object
  //   const [datePart, timePart] = egyptDateTime.split(' ');

  //   // Check if date and time parts are valid
  //   if (!datePart || !timePart) {
  //     throw new Error('Invalid Egypt date time format');
  //   }

  //   const [year, month, day] = datePart.split('-').map(Number);
  //   const [hours, minutes] = timePart.split(':').map(Number);

  //   // Create a new Date object with the Egypt local time
  //   const egyptDate = new Date(year, month - 1, day, hours, minutes);

  //   // Convert Egypt local time to UTC
  //   const utcYear = egyptDate.getUTCFullYear();
  //   const utcMonth = String(egyptDate.getUTCMonth() + 1).padStart(2, '0'); // Ensure two-digit month
  //   const utcDay = String(egyptDate.getUTCDate()).padStart(2, '0'); // Ensure two-digit day
  //   const utcHours = String(egyptDate.getUTCHours()).padStart(2, '0'); // Ensure two-digit hours
  //   const utcMinutes = String(egyptDate.getUTCMinutes()).padStart(2, '0'); // Ensure two-digit minutes

  //   // Construct the formatted UTC date string in "YYYY-MM-DD HH:mm" format
  //   return `${utcYear}-${utcMonth}-${utcDay} ${utcHours}:${utcMinutes}`;
  // }

  ClearSearch() {
    this.isDateSelected = false
    this.SelectedDate = ''
    this.getAllIssues(this.CurrentPageNumber);
  }

  generatePages() {
    this.pages = [];
    for (let i = 1; i <= this.PagesNumber; i++) {
      this.pages.push(i);
    }
  }

  getNextPage() {
    this.CurrentPageNumber++;
    this.getAllIssues(this.CurrentPageNumber);
  }

  getPrevPage() {
    this.CurrentPageNumber--;
    this.getAllIssues(this.CurrentPageNumber);

  }

  populateYears(): void {
    const startYear = 2019;
    const currentYear = new Date().getFullYear();

    for (let year = startYear; year <= currentYear; year++) {
      this.years.push(year);
    }
  }
  onYearChange(event: Event): void {
    const target = event.target as HTMLSelectElement;
    if (target) {
      this.selectedYear = +target.value;
      this.DateString = this.selectedYear + "-" + this.selectedMonth
      this.tableData = []
      this.getAllIssues(1);
    }
  }

  sendNotification(count: number) {
    this.issueNotificationService.updateMenuItems(count);
  }
}

