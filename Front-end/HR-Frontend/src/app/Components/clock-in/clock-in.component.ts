import { Component } from '@angular/core';
import { MatDialog, MatDialogModule } from '@angular/material/dialog'
import { ClockInPopUpComponent } from '../clock-in-pop-up/clock-in-pop-up.component';

@Component({
  selector: 'app-clock-in',
  standalone: true,
  imports: [
    MatDialogModule
  ],
  templateUrl: './clock-in.component.html',
  styleUrl: './clock-in.component.css'
})
export class ClockInComponent {
  constructor(public dialog: MatDialog) {}

  openDialog(): void {
    this.dialog.open(ClockInPopUpComponent, {

    });
  }
  // openDialog(): void {
  //   this.dialog.open(ClockInPopUpComponent, {
  //     width: '300px',
  //     data: { message: 'Hello from ClockInComponent' }
  //   });
  // }
}

