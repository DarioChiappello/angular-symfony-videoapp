import { Component, OnInit } from '@angular/core';
import { User } from '../../models/user';
import { UserService } from '../../services/user.service';

@Component({
  selector: 'app-user-edit',
  templateUrl: './user-edit.component.html',
  styleUrls: ['./user-edit.component.css'],
  providers: [UserService]
})
export class UserEditComponent implements OnInit {

  public page_title: string;
  public user: User;
  public status: string;
  public indentity;
  public token;

  constructor(
    private _userService: UserService
  ) { 
    this.page_title = "Ajustes";
    
    this.indentity = this._userService.getIdentity();
    this.token = this._userService.getToken();

    this.user = new User(this.indentity.sub,
                          this.indentity.name,
                          this.indentity.surname,
                          this.indentity.email,'','ROLE_USER','');
  }

  ngOnInit(): void {
  }

  onSubmit(form){
    this._userService.update(this.token, this.user).subscribe(
      response =>{
        //this.status = 'success';
        console.log(response);
        if(response && response.status == 'success'){
          this.status = 'success';
          this.indentity = response.user;
          this.user = response.user;
          localStorage.setItem('identity', JSON.stringify(this.indentity));

        }else{
          this.status = 'error';
        }
      },
      error =>{
        this.status = 'error';
        console.log(error);
      }
    );

  }

}
