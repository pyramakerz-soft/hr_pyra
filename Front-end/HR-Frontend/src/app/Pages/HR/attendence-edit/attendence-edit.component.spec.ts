import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AttendenceEditComponent } from './attendence-edit.component';

describe('AttendenceEditComponent', () => {
  let component: AttendenceEditComponent;
  let fixture: ComponentFixture<AttendenceEditComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AttendenceEditComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AttendenceEditComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
