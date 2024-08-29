import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrDepartmentAddComponent } from './hr-department-add.component';

describe('HrDepartmentAddComponent', () => {
  let component: HrDepartmentAddComponent;
  let fixture: ComponentFixture<HrDepartmentAddComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrDepartmentAddComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrDepartmentAddComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
