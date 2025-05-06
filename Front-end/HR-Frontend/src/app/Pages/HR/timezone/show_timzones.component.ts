import { Component, OnInit } from '@angular/core';
import { Timezone } from '../../../Models/timeZone';
import { TimeZoneService } from '../../../Services/timezone.service';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';  // Import CommonModule

@Component({
  selector: 'app-timezone-list',
  standalone: true,  // Mark this as a standalone component
  imports: [CommonModule],  // Import CommonModule to use ngIf and ngFor
  templateUrl: './show_timzones.component.html',
  styleUrls: ['./show_timzones.component.css']
})
export class ShowTimezonesComponent implements OnInit {
  timezones: Timezone[] = [];

  constructor(private timezoneService: TimeZoneService,
    public router: Router) {}

  ngOnInit(): void {
    this.loadTimezones();
  }

  loadTimezones(): void {
    this.timezoneService.getAllTimezones().subscribe(
      (data) => {
        console.log(data);
        this.timezones = data;
      },
      (error) => {
        console.error('Error fetching timezones', error);
      }
    );
  }
  // Method to handle editing a timezone
  addTimezone(): void {
    this.router.navigateByUrl("HR/ShowTimezonesAdd" );
  }
  // Method to handle editing a timezone
  editTimezone(id: number): void {
    this.router.navigateByUrl("HR/ShowTimezonesEdit/" + id);
  }

  deleteTimezone(id: number | null): void {
    if (!id) return; // Handle null or undefined case
    
    if (confirm('Are you sure you want to delete this timezone?')) {
      this.timezoneService.deleteTimezone(id).subscribe(
        () => {
          this.loadTimezones();
        },
        (error) => {
          console.error('Error deleting timezone', error);
        }
      );
    }
  }
}
