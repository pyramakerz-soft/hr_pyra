import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClockService } from '../../../Services/clock.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-attendence-edit',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './attendence-edit.component.html',
  styleUrl: './attendence-edit.component.css'
})
export class AttendenceEditComponent {
  data: any = new EmployeeDashboard("", "", "", "", "", "", "", "","","", []);
  ClockInAfterClockOut: boolean = false;
  DataIsNotTheSame: boolean = false;

  ClockInEgyptFormat:string=""
  ClockOutEgyptFormat:string=""
  


  constructor(private router: Router, public ClockServ: ClockService) {

    const navigation = this.router.getCurrentNavigation();
    if (navigation?.extras.state) {
      this.data = navigation.extras.state['data'] as EmployeeDashboard;
      console.log(this.data)
      this.data.formattedClockIn= this.transformUTCToEgyptTime(this.data.formattedClockIn);
      //this.data.formattedClockOut= this.transformUTCToEgyptTime(this.data.formattedClockOut);
    //   this.data.otherClocks = this.data.otherClocks.map((clock:any) => ({
    //     ...clock,
    //     formattedClockIn: this.transformUTCToEgyptTime(clock.formattedClockIn),
    //     formattedClockOut: this.transformUTCToEgyptTime(clock.formattedClockOut)
    //   }));

    }
    
    console.log(this.data.formattedClockIn )
  }

  CheckValidate() {
    this.data.formattedClockIn = this.data.formattedClockIn.replace("T", ' ')
    this.data.formattedClockOut = this.data.formattedClockOut.replace("T", ' ')
    if (this.data.formattedClockIn > this.data.formattedClockOut) {
      Swal.fire({
        icon: "error",
        title: "Clock Out must be after Clock In",
        confirmButtonText: "OK",
        confirmButtonColor: "#17253E",
      });
    }
    else if (this.data.formattedClockIn.split(' ')[0] !== this.data.formattedClockOut.split(' ')[0]) {
      Swal.fire({
        icon: "error",
        title: "Clock Out and Clock In must be in the same date",
        confirmButtonText: "OK",
        confirmButtonColor: "#17253E",
      });

    }
    else{
      this.data.formattedClockIn=this.transformEgyptTimeToUTC(this.data.formattedClockIn);
      this.data.formattedClockOut=this.transformEgyptTimeToUTC(this.data.formattedClockOut);

      this.SaveData();
    }
  }


  SaveData() {

    this.ClockServ.UpdateUserClock(this.data.userId, this.data.id, this.data.formattedClockIn, this.data.formattedClockOut).subscribe(
      (d: any) => {
        this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/" + this.data.userId)
      },
      (error) => {
        console.error('Error:', error);
        // Handle error
      }
    );

  }
  Cancel() {
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/" + this.data.userId)

  }


  transformUTCToEgyptTime(utcDateTime: string): string {
    // Parse the input UTC datetime string to a Date object
    const [datePart, timePart] = utcDateTime.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);
    
    // Create a new Date object with the UTC time
    const utcDate = new Date(Date.UTC(year, month - 1, day, hours, minutes));
  
    // Convert to Egypt local time using Intl.DateTimeFormat
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false, // Use 24-hour format
      timeZone: 'Africa/Cairo'
    };
  
    // Format the date into the desired output
    const egyptTimeFormatter = new Intl.DateTimeFormat('en-GB', options);
    const formattedDateParts = egyptTimeFormatter.formatToParts(utcDate);
  
    // Construct the formatted date string in "YYYY-MM-DD HH:mm" format
    const egyptDate = formattedDateParts.reduce((acc, part) => {
      if (part.type === 'year') acc['year'] = part.value;
      if (part.type === 'month') acc['month'] = part.value;
      if (part.type === 'day') acc['day'] = part.value;
      if (part.type === 'hour') acc['hour'] = part.value;
      if (part.type === 'minute') acc['minute'] = part.value;
      return acc;
    }, {} as Record<string, string>);
  
    // Return formatted string
    return `${egyptDate['year']}-${egyptDate['month']}-${egyptDate['day']} ${egyptDate['hour']}:${egyptDate['minute']}`;
  }



  transformEgyptTimeToUTC(egyptDateTime: string): string {
    // Parse the input Egypt local datetime string to a Date object
    const [datePart, timePart] = egyptDateTime.split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hours, minutes] = timePart.split(':').map(Number);
  
    // Create a new Date object with the Egypt local time
    const egyptDate = new Date(year, month - 1, day, hours, minutes);
  
    // Convert Egypt local time to UTC
    const utcYear = egyptDate.getUTCFullYear();
    const utcMonth = String(egyptDate.getUTCMonth() + 1).padStart(2, '0'); // Ensure two-digit month
    const utcDay = String(egyptDate.getUTCDate()).padStart(2, '0'); // Ensure two-digit day
    const utcHours = String(egyptDate.getUTCHours()).padStart(2, '0'); // Ensure two-digit hours
    const utcMinutes = String(egyptDate.getUTCMinutes()).padStart(2, '0'); // Ensure two-digit minutes
  
    // Construct the formatted UTC date string in "YYYY-MM-DD HH:mm" format
    return `${utcYear}-${utcMonth}-${utcDay} ${utcHours}:${utcMinutes}`;
  }

}