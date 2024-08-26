import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { EmployeeDashboard } from '../../../Models/employee-dashboard';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClockService } from '../../../Services/clock.service';

@Component({
  selector: 'app-attendence-edit',
  standalone: true,
  imports: [CommonModule,FormsModule],
  templateUrl: './attendence-edit.component.html',
  styleUrl: './attendence-edit.component.css'
})
export class AttendenceEditComponent {
  data: any = new EmployeeDashboard("", "", "", "", "", "", "", "", []); 
  

  constructor(private router: Router , public ClockServ:ClockService) {

    const navigation = this.router.getCurrentNavigation();
    if (navigation?.extras.state) {
      this.data = navigation.extras.state['data'] as EmployeeDashboard; 
      console.log(this.data)
    }
  }


  transformDateTime(dateTime: string): string {
    // Create a Date object from the input string
    const dateObj = new Date(dateTime);
  
    // Extract the date part and format it
    const datePart = dateObj.toISOString().split('T')[0]; // "2024-08-23"
  
    // Extract the time part, and remove leading zero from hours
    const timePart = dateObj.toTimeString().split(' ')[0]; // "07:51:00"
    const [hours, minutes] = timePart.split(':');
    const formattedTime = `${parseInt(hours, 10)}:${minutes}`; // "7:51"
  
    // Combine date and time parts
    return `${datePart} ${formattedTime}`;
  }



  SaveData(){


    this.data.formattedClockIn = this.data.formattedClockIn.replace("T", ' ')
    this.data.formattedClockOut = this.data.formattedClockOut.replace("T", ' ')

    this.ClockServ.UpdateUserClock(this.data.userId,this.data.id , this.data.formattedClockIn ,this.data.formattedClockOut).subscribe(
      (d: any) => {
        this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/"+this.data.userId)
      },
      (error) => {
        console.error('Error:', error);
        // Handle error
      }
    );

  }
  Cancel(){
    this.router.navigateByUrl("HR/HREmployeeAttendanceDetails/"+this.data.userId)

  }
}