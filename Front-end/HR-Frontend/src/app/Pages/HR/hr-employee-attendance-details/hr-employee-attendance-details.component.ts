import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';

interface data{
  Day:string,
  Date:string,
  Clock_in:string,
  Clock_out:string,
  Total_hours:string,
  Location_In:string,
  Location_out:string,
  Site:string,
}

@Component({
  selector: 'app-hr-employee-attendance-details',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-employee-attendance-details.component.html',
  styleUrl: './hr-employee-attendance-details.component.css'
})
export class HrEmployeeAttendanceDetailsComponent {
  tableData:data[]= [
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    // Add more data as needed
  ];
}
