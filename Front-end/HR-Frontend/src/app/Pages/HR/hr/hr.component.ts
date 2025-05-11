import { CommonModule } from '@angular/common';
import { Component, HostListener } from '@angular/core';
import { ActivatedRoute, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { SideBarComponent } from '../../../Components/Core/side-bar/side-bar.component';
import { IssueNotificationService } from '../../../Services/issue-notification.service';
import { IssuesService } from '../../../Services/issues.service';

@Component({
  selector: 'app-hr',
  standalone: true,
  imports: [SideBarComponent,CommonModule,RouterLink,RouterLinkActive,RouterOutlet],
  templateUrl: './hr.component.html',
  styleUrl: './hr.component.css'
})
export class HRComponent {
  isMenuOpen: boolean = false;
  count:number=0;

  menuItems = [
    { label: 'Dashboard', icon: 'fi fi-rr-table-rows', route: '/HR/HRDashboard', notificationCount: 0  },
    { label: 'Employee', icon: 'fas fa-home', route: '/HR/HREmployee' , notificationCount: 0 },
    { label: 'Roles', icon: 'fi fi-rs-chart-pie', route: '/HR/HRRole' , notificationCount: 0 },
    { label: 'Attendance ', icon: 'fi fi-rr-chart-simple', route: '/HR/HRAttendance' , notificationCount: 0 },
    { label: 'Locations', icon: 'fi fi-rr-chart-simple', route: '/HR/HRBounders' , notificationCount: 0 },
    { label: 'Department', icon: 'fi fi-rr-chart-simple', route: '/HR/HRDepartment' , notificationCount: 0 },
    { label: 'Issues', icon: 'fi fi-rr-chart-simple', route: '/HR/HRIssues', notificationCount: this.count }, 
    // { label: 'TimeZones', icon: 'fa-solid fa-clock-rotate-left', route: '/HR/ShowTimezones' , notificationCount: 0 },
    { label: 'Sign Out', icon: 'fi fi-bs-sign-out-alt transform rotate-180', route: '' , notificationCount: 0 },

  ];

  constructor(public router: Router, public activeRoute: ActivatedRoute,private issueNotificationService: IssueNotificationService ,public IssueServ : IssuesService) { }

  ngOnInit() {
    // Step 4: Subscribe to menuItems$ to get the latest count
    this.issueNotificationService.menuItems$.subscribe(count => {
      this.IssueServ.GetIssueCount().subscribe(
        (d:any)=>{
          this.count = d.data.count;
        });
    });
  }


  OpenMenu() {
    this.isMenuOpen = !this.isMenuOpen;
  }

  closeMenu() {
    this.isMenuOpen = false;
  }

  // Close dropdown if clicked outside
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    const dropdown = document.querySelector('.burger') as HTMLElement;

    if (dropdown && !dropdown.contains(target)) {
      this.isMenuOpen = false;
    }
  }

  // Cleanup event listener
  ngOnDestroy() {
    document.removeEventListener('click', this.onDocumentClick);
  }
}
