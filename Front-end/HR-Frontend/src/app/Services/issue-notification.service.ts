import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class IssueNotificationService {

  private menuItemsSource = new BehaviorSubject<number>(5);  // Initialized with default count value

  // Step 2: Create an Observable for components to subscribe to
  menuItems$ = this.menuItemsSource.asObservable();

  // Step 3: Method to update the count
  updateMenuItems(count: number) {
    this.menuItemsSource.next(count);
  }
}