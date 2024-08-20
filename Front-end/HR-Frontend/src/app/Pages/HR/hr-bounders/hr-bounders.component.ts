import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { BoundersPopUpComponent } from '../../../Components/bounders-pop-up/bounders-pop-up.component';
import { MatDialog } from '@angular/material/dialog';

interface data{
  location:string,
}

@Component({
  selector: 'app-hr-bounders',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hr-bounders.component.html',
  styleUrl: './hr-bounders.component.css'
})
export class HrBoundersComponent {
  constructor(public dialog: MatDialog) {}
  
  tableData:data[]= [
    { location: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt" },
    { location: "42 Abd Al Aziz Agamea, Sidi Gaber, Alexandria Governorate 5433112,Egypt" },
    { location: "42 Abd Al Aziz Agamea, Sidi Gaber" },
    // Add more data as needed
  ];

  openDialog(EditedLocation?:string): void {
    console.log(EditedLocation)
    if(EditedLocation != null){
      this.dialog.open(BoundersPopUpComponent, {
        data:{
          mode: 'edit',
          location: EditedLocation
        }
      });
    } else{
      this.dialog.open(BoundersPopUpComponent, {
        data:{
          mode: 'add',
        }
      });
    }
  }
}
