import { Component, ElementRef, ViewChild } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { TableComponent } from '../Core/table/table.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { EmployeeDashService } from '../../Services/employee-dash.service';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

interface DataObject {
  name?: string;
  email?: string;
  password?: string;
  phone?: string;
  contact_phone?: string;
  national_id?: string;
  department_id?: number;
  gender?: string;
  salary?: number;
  working_hours_day?: number;
  overtime_hours?: number;
  start_time?: string;
  end_time?: string;
  emp_type?: string;
  hiring_date?: string;
  roles?: string[];
  location_id?: number[];
  work_type_id?: number[];
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

  dataObjects: DataObject[] = []

  file:File | undefined
  
  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>, public employeeService:EmployeeDashService){}
  
  closeDialog(): void {
    this.dialogRef.close();
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.file = input.files[0];

      const reader = new FileReader();

      reader.onload = (e: ProgressEvent<FileReader>) => {
        const data = new Uint8Array(reader.result as ArrayBuffer);
        const workbook = XLSX.read(data, { type: 'array' });

        const worksheet = workbook.Sheets[workbook.SheetNames[0]];
        const json: any[][] = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

        const headers: string[] = json[0] as string[];
        const rows: any[][] = json.slice(1); 

        this.dataObjects = rows.map((row: any[]) => {
          let obj: DataObject = {};
          headers.forEach((header, index) => {
            obj[header as keyof DataObject] = row[index];
          });
          return obj;
        });
      };

      reader.readAsArrayBuffer(this.file);
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
            this.dataObjects = []
            Swal.fire({
              icon: "success",
              title: "Successfully Imported",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
              
            });
          }
        },
        (err) => {
          console.log(err.error)
          if(err.error.message.includes("Invalid data format")){
            Swal.fire({
              icon: "error",
              title: "Invalid",
              text: err.error.message, 
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
              
            });
          } else if(err.error.message.includes("Duplicate entry")){
            Swal.fire({
              icon: "error",
              title: "Duplicate Entry",
              html: err.error.message.replace(/\n/g, '<br>'), 
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",
              
            });
          }
          if(this.fileInput){
            this.fileInput.nativeElement.value = ''; 
            this.file = undefined
            this.dataObjects = []
          }
        }
      )
    }else{
      Swal.fire({
        icon: "question",
        title: "Select an Excel File",
        confirmButtonText: "OK",
        confirmButtonColor: "#FF7519",
        
      });
    }
  }
}


// "Duplicate entry found for email \"khaled2@test.com\" in row 2\nDuplicate entry found for email \"jane2@example.com\" in row 3"
"Invalid data format: Missing headers in the Excel file. Missing: name, email, password, phone, contact_phone, national_id, department_id, gender, salary, working_hours_day, overtime_hours, start_time, end_time, emp_type, hiring_date, roles, location_id, work_type_id"