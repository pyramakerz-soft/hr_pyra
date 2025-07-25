import { CommonModule } from '@angular/common';
import { Component, ElementRef, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { EmployeeDashService } from '../../Services/employee-dash.service';
import { TableComponent } from '../Core/table/table.component';

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

  dataObjects: any[] = [];
  displayedColumns: string[] = [];

  file:File | undefined
  
  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>, public employeeService:EmployeeDashService){}
  
  closeDialog(): void {
    this.dialogRef.close();
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      const selectedFile = input.files[0];
      const fileName = selectedFile.name;
      const fileExtension = fileName.split('.').pop()?.toLowerCase();
      if (fileExtension !== 'xlsx') {
        Swal.fire({
          icon: "error",
          title: "Invalid File Type",
          text: "Please select an Excel file with .xlsx extension.",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",
        });
        if (this.fileInput) {
          this.fileInput.nativeElement.value = '';
        }
        this.file = undefined;
        this.dataObjects = [];
        this.displayedColumns = [];
        return;
      }
      this.file = selectedFile;
      const reader = new FileReader();
      reader.onload = (e: ProgressEvent<FileReader>) => {
        const data = new Uint8Array(reader.result as ArrayBuffer);
        const workbook = XLSX.read(data, { type: 'array' });
        const worksheet = workbook.Sheets[workbook.SheetNames[0]];
        const json: any[][] = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        const headers: string[] = (json[0] as string[]).map(h => h?.toString().trim().toLowerCase().replace(/\s+/g, '_'));
        const rows: any[][] = json.slice(1);
        this.displayedColumns = headers;
        this.dataObjects = rows.map((row: any[]) => {
          let obj: any = {};
          headers.forEach((header, index) => {
            if (header) {
              obj[header] = row[index];
            }
          });
          return obj;
        });
      };
      reader.readAsArrayBuffer(this.file);
    }
  }

  ImportEmployees(){
    if(this.file){
      this.employeeService.ImportEmployee(this.file).subscribe(
        (d:any)=>{
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
          if(err.error.message){
            Swal.fire({
              icon: "error",
              title: "Invalid",
              html: err.error.message.replace(/(Row \d+:)/g, '<strong>$1</strong>').replace(/\n/g, '<br>'), 
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
