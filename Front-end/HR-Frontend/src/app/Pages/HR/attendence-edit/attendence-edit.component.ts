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
  data: any = new EmployeeDashboard("", "", "", "", "", "", "", "", []);
  ClockInAfterClockOut: boolean = false;
  DataIsNotTheSame: boolean = false;



  constructor(private router: Router, public ClockServ: ClockService) {

    const navigation = this.router.getCurrentNavigation();
    if (navigation?.extras.state) {
      this.data = navigation.extras.state['data'] as EmployeeDashboard;
      console.log(this.data)
    }
    console.log(this.data)

    console.log(this.data.formattedClockIn  )
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
}