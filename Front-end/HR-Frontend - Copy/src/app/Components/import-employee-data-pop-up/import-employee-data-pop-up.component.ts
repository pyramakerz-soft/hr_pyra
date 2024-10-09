import { Component } from '@angular/core';
import { MatDialogRef } from '@angular/material/dialog';
import { TableComponent } from '../Core/table/table.component';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

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
  tableData:data[]= [
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    { Day: "Saturday", Date: 'Apr 28th 2024', Clock_in: "10:25 Am", Clock_out: "10:25 Am", Total_hours: "8:00 H", Location_In: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Location_out: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt", Site: "Home"},
    // Add more data as needed
  ];

  constructor(public dialogRef: MatDialogRef<ImportEmployeeDataPopUpComponent>){}
  
  closeDialog(): void {
    this.dialogRef.close();
  }
}
