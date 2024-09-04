import { Component, ElementRef, ViewChild } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { TableComponent } from '../Core/table/table.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { EmployeeDashService } from '../../Services/employee-dash.service';
import Swal from 'sweetalert2';

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
  selector: 'app-import-employee-data-pop-up',
  standalone: true,
  imports: [TableComponent, CommonModule, FormsModule],
  templateUrl: './import-employee-data-pop-up.component.html',
  styleUrl: './import-employee-data-pop-up.component.css'
})
export class ImportEmployeeDataPopUpComponent {
  @ViewChild('fileInput') fileInput: ElementRef | undefined;  
  
  tableData:data[]= [
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
  ];

  file:File | undefined
  
  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>, public employeeService:EmployeeDashService){}
  
  closeDialog(): void {
    this.dialogRef.close();
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.file = input.files[0];
      console.log(this.file)
    }
  }
// "Failed to import users from Excel: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'khaled2@test.com' for key 'users.users_email_unique' (Connection: mysql, SQL: insert into `users` (`name`, `email`, `password`, `phone`, `contact_phone`, `national_id`, `code`, `department_id`, `gender`, `serial_number`, `updated_at`, `created_at`) values (Khaled Ahmed, khaled2@test.com, $2y$12$UsSDoBurrt2CVCsJBwuxWe1sk41nlNRqywCPteEcaRggOHd.cJJ7m, 1234567897, 1234509879, 12345678901235, SOFT-8871, 1, m, ?, 2024-09-04 09:56:53, 2024-09-04 09:56:53))"
  ImportEmployees(){
    if(this.file){
      this.employeeService.ImportEmployee(this.file).subscribe(
        (d:any)=>{
          console.log(d)
          if(this.fileInput){
            this.fileInput.nativeElement.value = ''; 
            this.file = undefined
            Swal.fire({
              icon: "success",
              title: "Successfully Imported",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
              
            });
          }
        },
        (err) => {
          console.log(err.message)
          if(this.fileInput){
            this.fileInput.nativeElement.value = ''; 
            this.file = undefined
          }
        }
      )
    }
  }
}
